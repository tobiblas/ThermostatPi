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
    #dbName = "/home/pi/temperature.db"
    dbName = "/Users/tobiblas/Sites/ThermostatPi/thermostat.db"
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
