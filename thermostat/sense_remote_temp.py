#!/usr/bin/env python
# -*- coding: utf-8 -*-
from __future__ import with_statement
from subprocess import call
import time
import pytz
import sys
import urllib2
import threading
from urllib2 import URLError
import json
import db
from datetime import datetime
import RPi.GPIO as GPIO

RELAY_ON = 1
RELAY_OFF = -1
RELAY_GPIO_PIN = 4

if len(sys.argv) < 2:
    print "Usage: python sense_remote_temp.py temp_home <OPTIONAL:label>"
    sys.exit()
location = sys.argv[1]
label = None
if len(sys.argv) == 3:
    label = sys.argv[2]

if not location.endswith("/"):
    location += "/"

def fetchFrom(url, attemptsLeft):
    print "Fetching from " + url + " attempts left " + str(attemptsLeft)
    try:
        response = urllib2.urlopen(url)
        if not response.code == 200:
            response.close()
            print "ERROR! Did not get 200 response"
            if (attemptsLeft > 0):
                print "Retrying..."
                return fetchFrom(url, attemptsLeft-1)
        else:
            body = response.read()
            print "Got response: " + body.rstrip()
            response.close()
            return body.rstrip()
    except:
        if (attemptsLeft > 0):
            print "Retrying..."
            return fetchFrom(url, attemptsLeft-1)

def fetchOutdoorTemp(myprops):

    apiKey = myprops['openweatherApiKey']
    location = myprops['outdoorLocation']
    unit = myprops['unit']

    requestStr = 'http://api.openweathermap.org/data/2.5/weather?q=' + location + '&appid=' + apiKey + '&units=metric'
    temp = fetchFrom(requestStr, 3)

    j = json.loads(temp)
    print "got temp " + str(j['main']['temp']) + " in " + location
    #saveTemp(float(j['main']['temp']), location)
    return float(j['main']['temp'])


def getMyProps():
    myprops = {}
    with open(location + 'admin.properties', 'r') as f:
        for line in f:
            line = line.rstrip() #removes trailing whitespace and '\n' chars
            if ":" not in line: continue #skips blanks and comments w/o =
            if line.startswith("#"): continue #skips comments which contain =
            k, v = line.split(":", 1)
            myprops[k] = v
    print myprops
    return myprops

def remoteFetchTemp(myprops):
    print "Fetching remote temp"

    ips = myprops['deviceIPs']
    devices = myprops['devices']
    devicesSplit = devices.split(",")

    i = 0
    tempSum = 0.0
    tempDict = {}

    for line in ips.split(","):
        url = "http://" + line.strip() + "/thermometer/current_temp.php"
        temperature = fetchFrom(url, 3)
        if temperature is None:
            print "Could not connect to " + url + ". trying one more time in 10 seconds"
            time.sleep(10)
            temperature = fetchFrom(url, 3)
        if temperature is None:
            print "Failed once more."
            logError("Failed to get remote temp for " + devicesSplit[i])
        else:
            t = float(temperature) - 273.15
            tempSum += t
            print "got temp " + str(t) + " in " + devicesSplit[i]
            tempDict[devicesSplit[i]] = t
            #saveTemp(float(temperature) - 273.15, devicesSplit[i])
            i = i + 1
    if (i == 0):
        return None
    print "Average temp is " + str(tempSum / i)

    if 'openweatherApiKey' in myprops.keys() and myprops['openweatherApiKey'].strip() != "":
        try:
            t = fetchOutdoorTemp(myprops)
            tempDict['outside'] = t
        except:
            print "Failed to get open wheather temp"
            logError("Failed to get open wheather temp")

    tempDict["average"] = float(tempSum / i)
    return tempDict

def logError(error):
    db.logError(error)

def logStatus(fromVal, toVal, average, targetTemp, threshold, outside):
    db.saveStatus(fromVal, toVal, average, targetTemp, threshold, outside)

def setRelay(fromVal, toVal, average, targetTemp, threshold, outside):
    if (toVal == RELAY_ON):
        #turn on
        logStatus(fromVal, toVal, average, targetTemp, threshold, outside)
        GPIO.output(RELAY_GPIO_PIN, True)
    elif (toVal == RELAY_OFF):
        #turn off
        logStatus(fromVal, toVal, average, targetTemp, threshold, outside)
        GPIO.output(RELAY_GPIO_PIN, False)

# returns [status, timestamp]
def getLastStatus():
    return db.getLastStatus()

def getNow():
    tz = pytz.timezone('Europe/Berlin')
    tzOffsetInSeconds = tz.utcoffset(datetime.now()).total_seconds()
    epochTime = int(time.time() + tzOffsetInSeconds)
    return epochTime

def main():
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(RELAY_GPIO_PIN, GPIO.OUT)

    myprops = getMyProps()
    targetTemp = float(myprops['targetTemp'])
    threshold = float(myprops['threshold'])
    graceTimeMinutes = int(myprops['graceTimeMinutes'])
    temps = remoteFetchTemp(myprops)
    average = temps.get("average")
    outside = temps.get("outside")

    lastStatus = getLastStatus()
    lastStatusChangeTime = db.getLastStatusChange()
    print "last status " + str(lastStatus) + ", lastStatusChangeTime " + str(lastStatusChangeTime)

    #empty database
    if (lastStatusChangeTime == 0):
        setRelay(0, RELAY_ON, average, targetTemp, threshold, outside)
        lastStatusChangeTime = db.getLastStatusChange()
        lastStatus = getLastStatus()

    if (temps is None or average is None or average < 15):
        logError("ERROR. Could not get temp data. Will set relay on after gracetime: " + str(graceTimeMinutes) + " minutes. Average temp was " + str(average))
        #if last status > graceTimeMinutes then make sure relay is on.
        if (getNow() > lastStatusChangeTime + graceTimeMinutes*60):
            setRelay(lastStatus, RELAY_ON, average, targetTemp, threshold, outside)
        return;

    # if last status < gracetime then return.
    print "now: " + str(getNow()) + " turnpoint: " + str(lastStatusChangeTime + graceTimeMinutes*60)
    if (getNow() < lastStatusChangeTime + graceTimeMinutes*60):
        print "gracetime not over yet. Return"
        return


    # if average within threshold of targetTemp then do nothing and log STATUS_QUO
    if (average > targetTemp-threshold and average < targetTemp+threshold):
        print "average within threshold. Do nothing"
    # if average < targetTemp-threshold then check if relay is off. If Off then turnOn Relay if gracetime met
    elif (average < targetTemp-threshold and lastStatus == RELAY_OFF):
        setRelay(RELAY_OFF, RELAY_ON, average, targetTemp, threshold, outside)
    # if average > targetTemp+threshold then check if relay is on. If On then turnOffRelay if gracetime met
    elif (average > targetTemp+threshold and lastStatus == RELAY_ON):
        setRelay(RELAY_ON, RELAY_OFF, average, targetTemp, threshold, outside)
    else:
        logStatus(lastStatus, lastStatus, average, targetTemp, threshold, outside)
        print "No change needed"

main()
