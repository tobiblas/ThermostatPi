# -*- coding: utf-8 -*-
from __future__ import with_statement
from subprocess import call
import time
import sys
import urllib2
import threading
import RPi.GPIO as GPIO

if len(sys.argv) != 2:
    print "Usage: python sense_motion.py <alarm home folder>"
    sys.exit()
location = sys.argv[1]

if not location.endswith("/"):
    location += "/"

buzzerOn = False

def triggerPiezo():
    global buzzerOn
    if buzzerOn:
        return
    buzzerOn = True
    c = 0
    frequencyModder = 0
    start = int(round(time.time() * 1000))
    t_end = time.time() + 60
    while time.time() < t_end:
        c = c + 1
        time.sleep(.0000005*c)
        GPIO.output(BUZZER_PIN, True)
        time.sleep(.0000005*c)
        GPIO.output(BUZZER_PIN, False)
        t = int(round(time.time() * 1000))
        if (t - start) / 200 != frequencyModder:
            frequencyModder = (t - start) / 200
            c = 0
    buzzerOn = False

def triggerAlarm():
    print "The alarm went off. Trigger php server"
    
    myprops = {}
    with open(location + 'settings.properties', 'r') as f:
        for line in f:
            line = line.rstrip() #removes trailing whitespace and '\n' chars
            
            if ":" not in line: continue #skips blanks and comments w/o =
            if line.startswith("#"): continue #skips comments which contain =
            
            k, v = line.split(":", 1)
            myprops[k] = v
    print myprops

#if alarmOn and (myprops['sound'].strip() == 'true' or myprops['sms'].strip() == 'true'):
#        print "triggering android app"
#        message = "Alarm triggered. DetectorID = " + ""
#        messageToAndroidApp(myprops['sound'].strip() == 'true',
#                        myprops['sms'].strip() == 'true',
#                        message,
#                        myprops['phonenumbers'].strip())
#    else:
#        print "system not configured to use phone or alarm is off"

    # trigger call to php server
    url = myprops['serverURL'].strip() + 'trigger_alarm.php?triggerID=' + myprops['detectorID'].strip()
    print "calling " + url
    response = urllib2.urlopen(url)
    if not response.code == 200:
        print "ERROR! Did not get 200 response"
    else:
        body = response.read()
        if "ALARMON" in body:
            piezo_thread = threading.Thread(target=triggerPiezo)
            piezo_thread.start()
    response.close()


GPIO.setmode(GPIO.BCM)
PIR_PIN = 7
BUZZER_PIN = 8
LED_PIN = 23
GPIO.setup(PIR_PIN, GPIO.IN)
GPIO.setup(LED_PIN, GPIO.OUT)
GPIO.output(LED_PIN, False)
GPIO.setup(BUZZER_PIN, GPIO.OUT)
GPIO.output(BUZZER_PIN, False)


def MOTION(PIR_PIN):
    print 'Motion Detected!'
    GPIO.output(LED_PIN, True)
    triggerAlarm()
    time.sleep(3)
    GPIO.output(LED_PIN, False)
               

print 'Motion detection script loaded (CTRL+C to exit)'
print 'Ready'

try:
               GPIO.add_event_detect(PIR_PIN, GPIO.RISING, callback=MOTION)
               while 1:
                              time.sleep(100)
except KeyboardInterrupt:
               print ' Quit'
               GPIO.cleanup()
