#!/usr/bin/env python
# -*- coding: utf-8 -*-
from __future__ import with_statement
from subprocess import call
import time
import sys
import urllib2
import threading
from urllib2 import URLError
import sqlite3
import json
import pytz
from datetime import datetime

if len(sys.argv) < 2:
    print "Usage: python sense_remote_temp.py temp_home <OPTIONAL:label>"
    sys.exit()
location = sys.argv[1]
label = None
if len(sys.argv) == 3:
    label = sys.argv[2]

if not location.endswith("/"):
    location += "/"

addTime = 0

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
    except URLError, e:
        if (attemptsLeft > 0):
            print "Retrying..."
            return fetchFrom(url, attemptsLeft-1)

def createTables(cursor):
    sql = "create table if not exists status (status INTEGER, timestamp INTEGER)"
    cursor.execute(sql)
    sql = "create table if not exists error (message TEXT, timestamp INTEGER)"
    cursor.execute(sql)
    sql = "create table if not exists temperature (timestamp INTEGER PRIMARY KEY, location TEXT, temp REAL, label TEXT)"
    cursor.execute(sql)
    sql = "create index if not exists TEMP_IDX on temperature(timestamp)"
    cursor.execute(sql)


def saveTemp(temperature, device):
    global addTime
    dbName = "/home/pi/temperature.db"
    conn = sqlite3.connect(dbName)
    c = conn.cursor()
    createTables(c)
    tz = pytz.timezone('Europe/Berlin')
    tzOffsetInSeconds = tz.utcoffset(datetime.now()).total_seconds()
    epoch_time = int(time.time() + tzOffsetInSeconds) + addTime
    addTime = addTime + 1
    sql = ""
    if label is None:
        sql = "insert into temperature values("+ str(epoch_time) +", \""+ device +"\", " + str(temperature) + ",null)"
    else:
        sql = "insert into temperature values("+ str(epoch_time) +", \""+ device +"\", " + str(temperature) + ",\"" + label + "\")"
    print sql
    c.execute(sql)
    conn.commit()
    conn.close()

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

def logError(error):
    print "should log error here"

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
            tempDict[myprops['outdoorLocation']] = t
        except:
            print "Failed to get open wheather temp"
            logError("Failed to get open wheather temp")

    tempDict["average"] = float(tempSum / i)
    return tempDict

def logStatus(value):
    print "should log here"
    #todo

def setRelay(value):
    if (value == 1):
        #turn on
        logStatus(1)
    elif (value == -1):
        #turn off
        logStatus(-1)

def getLastStatus():
    return [343545454,1]

def getNow():
    tz = pytz.timezone('Europe/Berlin')
    tzOffsetInSeconds = tz.utcoffset(datetime.now()).total_seconds()
    epochTime = int(time.time() + tzOffsetInSeconds)
    return epochTime

def main():
    myprops = getMyProps()
    targetTemp = myprops['targetTemp']
    threshold = myprops['threshold']
    graceTimeMinutes = myprops['graceTimeMinutes']

    print "getting temps"
    temps = remoteFetchTemp(myprops)
    print temps

    print "getting last status"
    lastStatus = getLastStatus()
    if (temps is None):
        logError("ERROR. Could not get temp data")
        #if last status > graceTimeMinutes then make sure relay is on.
        if (getNow() > lastStatus[0] + graceTimeMinutes*60):
            if (lastStatus[1] != 1):
                setRelay(1)
        return;

    # if last status < gracetime then return.
    if (getNow() < lastStatus[0] + graceTimeMinutes*60):
        print "gracetime not over yet. Return"
        return

    
    # if average within threshold of targetTemp then do nothing and log STATUS_QUO
    # if average < targetTemp then check status. If On then turnOffRelay if gracetime met and log else log STATUS_QUO
    # if average < targetTemp then check status. If On then turnOffRelay if gracetime met and log else log STATUS_QUO


main()
