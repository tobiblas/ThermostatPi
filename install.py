import os
import subprocess
from tempfile import mkstemp
from shutil import move
from os import remove, close

################# APACHE2 ####################################

print "Checking for apache installation..."

apache2path = subprocess.Popen("which apache2", shell=True, stdout=subprocess.PIPE).stdout.read()

if "apache2" not in apache2path:
    print "installing apache web server (might take a while..)"
    print subprocess.Popen("sudo apt-get install apache2 -y", shell=True, stdout=subprocess.PIPE).stdout.read()
else:
    print "apache already installed. Skipping"
    print "-----------"

################# PHP ########################################

print "Checking for php installation..."

phpPath = subprocess.Popen("which php", shell=True, stdout=subprocess.PIPE).stdout.read()

if "php" not in phpPath:
    print "installing php  (might take a while..)"
    print subprocess.Popen("sudo apt-get install php -y", shell=True, stdout=subprocess.PIPE).stdout.read()
else:
    print "php already installed. Skipping"

print "-----------"

################# SQLITE3 ########################################

print "Checking for sqlite3 installation..."

phpPath = subprocess.Popen("which sqlite3", shell=True, stdout=subprocess.PIPE).stdout.read()

if "sqlite3" not in phpPath:
    print "installing sqlite3  (might take a while..)"
    print subprocess.Popen("sudo apt-get install sqlite3 -y", shell=True, stdout=subprocess.PIPE).stdout.read()
else:
    print "sqlite3 already installed. Skipping"

# create thermostat.db

print "-----------"

################# MOVE PHP PAGE TO RIGHT PLACE ###############

print "adding php admin page for thermostat."
print "installing php pages in /var/www/html/"

print subprocess.Popen("sudo mkdir -p /var/www/html/thermostat && sudo cp -R php/* /var/www/html/thermostat", shell=True, stdout=subprocess.PIPE).stdout.read()

print "-----------"

#################THERMO FILES, CONFIG ETC######################
thermoPath = "/home/pi/thermostat"

print "Installing thermostat files in " + thermoPath
print subprocess.Popen("mkdir -p " + thermoPath, shell=True, stdout=subprocess.PIPE).stdout.read()
if not thermoPath.endswith("/"):
	thermoPath += "/"
print subprocess.Popen("cp -R thermostat/* " + thermoPath, shell=True, stdout=subprocess.PIPE).stdout.read()


print "todo. add this to crontab: 0 4,15 * * * python /home/pi/thermometer/sense_remote_temp.py /var/www/html/thermometer 1> /home/pi/templog.txt 2> /home/pi/templog.err"

##############################################################

print
print "Congratulation! Now go to <IP of your raspberry>/thermostat and configure your thermostat."
