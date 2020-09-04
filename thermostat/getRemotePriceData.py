#!/usr/bin/env python
# -*- coding: utf-8 -*-

import bs4 as bs
import requests
import json
import time
import db
import datetime

#
# Purpose with this program is that it shall run just after midnight.
# * fetch latest price from one of several sources
# * Verify that the date for the price is correct and that it worked otherwise report error via mail.
# * save price to the database
#

# LEFT TO DO:
# Steg 1. Installera denna fil på thermostaten så att vi börjar spara data. crontab behövs.
# FIXA SÅ DET FINNS EN SEND MAIL (ERRORHANDLING)
# lägg in history view på thermostaten genom att deploya senaste.

#så här skickar vi mail på alarmet:
#shell_exec('echo "' . $content . '" | mail -s "' . $subject . '" ' . $address);
#python kallar på php som kallar på bash.

def logError(message):
    print(message)
    #LOG ERROR OR SEND AN EMAIL THAT WE COULD NOT GET PRICES.
    #TRY ONE OF THE OTHER SOURCES.

def savePrice(timestamp, price):
    db.savePrice(timestamp, price)

def getPage(url):
    try:
        return requests.get(url)
    except:
        return None

def getSoup(page):
    try:
        return bs.BeautifulSoup(page.text,'html.parser')
    except:
        return None

#Gets data for same day as request is made. Breakpoint for new data seems to be 13:00
#so request must be made between 00:00 - 13:00.
def getPriceDataNordPool():
    page = getPage('https://www.nordpoolgroup.com/api/marketdata/page/29?currency=,,,EUR&entityName=SE4')
    if (page == None):
        page = getPage('https://www.nordpoolgroup.com/api/marketdata/page/29?currency=,,,EUR&entityName=SE4')
        if (page == None):
            logError("Error for getting page for nordpool")
            return None
    jsonResponse = json.loads(page.content)
    result = []
    i = 0
    dateForData = jsonResponse["data"]["Rows"][0]["Columns"][0]["Name"]
    today = time.strftime('%d-%m-%Y')
    if today not in dateForData:
        logError("Could not find data for correct date " + today + ". Was " + dateForData)
        return None
    while i < 24:
        data = jsonResponse["data"]["Rows"][i]["Columns"][0]["Value"]
        result.append(float(data.replace(',', '.')))
        i += 1
    return result

#Gets data for same day as request is made. Breakpoint for new data seems to be midnight
def getPriceDataElenDotNu():
    print('getting data from elen.nu')
    page = getPage('https://elen.nu/dagens-spotpris/se4-malmo/')
    print('Got page ' + str(page.text))
    if (page == None):
        page = getPage('https://elen.nu/dagens-spotpris/se4-malmo/')
        if (page == None):
            logError("Error for getting page elen.nu")
            return None
    soup = getSoup(page)
    if (soup == None):
        logError("Error for getting soup elen.nu")
        return None
    tableDatas = soup.find("table", class_="w-full").find_all("td")
    today = time.strftime('%Y-%m-%d')
    if today not in tableDatas[0].getText():
        logError("Could not find data for correct date " + today)
        return None
    i = 0
    result = []
    for td in tableDatas:
        if i % 2 == 0:
            i = i + 1
            continue
        priceString = tableDatas[i].getText()
        result.append(float(priceString.split("öre")[0].strip().replace(',', '.')))
        i = i + 1
    return result

def getPriceData():
    #Method 1. Get from elen.nu
    priceList = []
    try:
        priceList = getPriceDataElenDotNu()
    except Exception as e:
        logError("Error for getting soup elen.nu: " + str(e))
    if (len(priceList) == 24):
        return priceList

    #Method 2. Get from nordpool
    try:
        priceList = getPriceDataNordPool()
    except Exception as e:
        logError("Error for getting pricelist from nordpool: " + str(e))

    return priceList

def getAndSaveDataForToday():
    priceList = getPriceData()
    print(str(priceList))
    if (priceList != None):
        dt = datetime.datetime.now()
        currentHour = 0
        for price in priceList:
            dt = datetime.datetime.now()
            dt = dt.replace(hour=currentHour, minute=0, second=0, microsecond=0)
            savePrice(dt, price)
            currentHour = currentHour + 1

    print(priceList)

getAndSaveDataForToday()
