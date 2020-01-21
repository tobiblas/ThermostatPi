import sqlite3
import pytz
from datetime import datetime
import time

def createTables(cursor):
    #electricity table. Saves price data per day. 24 hour prices per dag.
    sql = "create table if not exists pricedata (timestamp INTEGER, price REAL)"
    cursor.execute(sql)
    sql = "create unique index if not exists unique_idx_pricedata on pricedata(timestamp, price)"
    cursor.execute(sql)
    sql = "create table if not exists status (fromStatus INTEGER, toStatus INTEGER, average REAL, targetTemp REAL, threshold REAL, outside REAL, timestamp INTEGER)"
    cursor.execute(sql)
    sql = "create table if not exists error (message TEXT, timestamp INTEGER)"
    cursor.execute(sql)
    sql = "create index if not exists TIMESTAMP_IDX on status(timestamp)"
    cursor.execute(sql)
    #sql = "create table if not exists temperature (timestamp INTEGER PRIMARY KEY, location TEXT, temp REAL, label TEXT)"
    #cursor.execute(sql)
    #sql = "create index if not exists TEMP_IDX on temperature(timestamp)"
    #cursor.execute(sql)


def getConnection():
    dbName = "/home/pi/thermostat.db"
    #dbName = "/Users/tobiblas/Sites/ThermostatPi/thermostat.db"
    conn = sqlite3.connect(dbName)
    cursor = conn.cursor()
    createTables(cursor)
    return (conn, cursor)

def executeSql(db, sql):
    print ("executing: " + sql)
    db[1].execute(sql)
    db[0].commit()
    db[0].close()

def getNow():
    tz = pytz.timezone('Europe/Berlin')
    tzOffsetInSeconds = tz.utcoffset(datetime.now()).total_seconds()
    epochTime = int(time.time() + tzOffsetInSeconds)
    return epochTime

def getGmtTime(timestamp):
    tz = pytz.timezone('Europe/Berlin')
    tzOffsetInSeconds = tz.utcoffset(datetime.now()).total_seconds()
    epochTime = int(timestamp.timestamp() + tzOffsetInSeconds)
    return epochTime

def logError(error):
    db = getConnection()
    sql = "insert into error values(\""+ str(error) +"\", "+ str(getNow()) +")"
    executeSql(db, sql)

def getLastStatus():
    sql = "select toStatus from status order by timestamp desc limit 1"
    db = getConnection()
    db[1].execute(sql)
    rows = db[1].fetchall()
    result = 0
    if rows:
        result = rows[0][0]
    else:
        print ("No last status.")
    db[0].close()
    return result

def isExpensiveHour(timestamp, glidingAverage, thersholdPercent):
    sql = "select price, (t1.timestamp - " + str(timestamp) + ") as offset from pricedata as t1 order by abs(t1.timestamp - " + str(timestamp) + ") limit 2"
    #this query will return result like: 20.1, -300 | 24.2, 3300    this means we are 300 sekonds after closest point and 3300 seconds before next.  
    
    print ("executing: " + sql)
    db = getConnection()
    db[1].execute(sql)
    rows = db[1].fetchall()
    result = 0
    offset = 0
    result2 = -1
    offset2 = -1
    if rows:
        result = rows[0][0]
        offset = rows[0][1]
        if len(rows) == 2:
            result2 = rows[1][0]
            offset2 = rows[1][1]
    else:
        print ("No last status.")
    db[0].close()
    print ("isExpensiveHour found price: " + str(result) + "," + str(result2) + " offsets: " + str(offset) + "," + str(offset2) )
    if (result2 == -1 and offset2 == -1):
        print ("only one dot of data")
        return result > thersholdPercent * glidingAverage
    closestIsExpensive = result > thersholdPercent * glidingAverage
    secondIsExpensive = result2 > thersholdPercent * glidingAverage
    #return result > thersholdPercent * glidingAverage
    if closestIsExpensive and secondIsExpensive:
        print ("Between two expensive dots")
        return True;
    if closestIsExpensive and secondIsExpensive != True:
        print ("Closest is expensive")
        return True;
    if closestIsExpensive != True and secondIsExpensive:
        print ("Second closest is expensive")
        return offset > 0
    print ("not expensive hour") 
    return False

def getGlidingAverage(timestamp):
    fromTime = timestamp - 3600 * 24 * 7
    db = getConnection()
    sql = "select avg(price) from pricedata where timestamp > " + str(fromTime) + " and timestamp < " + str(timestamp);
    db[1].execute(sql)
    rows = db[1].fetchall()
    result = 0
    if rows:
        result = rows[0][0]
    else:
        print ("No last status.")
    db[0].close()
    return result

def savePrice(timestamp, price):
    db = getConnection()
    sql = "insert into pricedata values(" + str(getGmtTime(timestamp)) + "," + str(price) + ")"
    executeSql(db, sql)

def saveStatus(fromStatus, toStatus, average, targetTemp, threshold, outside):
    db = getConnection()
    sql = "insert into status values("+ str(fromStatus) +", " + str(toStatus) +", " + str(average) +", " + str(targetTemp) +", " + str(threshold) +", " + str(outside) +", "+ str(getNow()) +")"
    executeSql(db, sql)

def getLastStatusChange():
    timestamp = 0
    sql = "select timestamp from status where toStatus != fromStatus order by timestamp desc limit 1"
    db = getConnection()
    db[1].execute(sql)
    rows = db[1].fetchall()
    if rows:
        timestamp = rows[0][0]
    db[0].close()
    print ("last change was " + str(timestamp))
    return timestamp
