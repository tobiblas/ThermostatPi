I use this software on a raspberry pi connected to a relay. The relay controls a pump which pums the water around in my heating system. 

The software uses 1-many sources of temperatura data to determine wether the pump should be on or off. I also fetches electricity price data for Sweden which can optionally be used in the decision to turn on/off the pump

This setup saves me ~10% of my electricity bill since I can avoid heating my house on expensive hours.


#enable sqlite3 for php
sudo apt-get install php5-sqlite

#DATABASE STUFF
create the database thermostat.db
make sure it is read and writeable PLUS that the folder it is in is readable/writable by php

TODO: backup database to keep history if memory gets corrupted.
