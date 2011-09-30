# This code is provided under a Creative Commons Attribution license (for
# details see: http://creativecommons.org/licenses/by/3.0/). In summary, you
# are free to use the code for any purpose as long as you remember to mention
# my name (Torben Sko) at some point. Also please note that my code is
# provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING THE WARRANTY OF
# DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.

# provides a simple plot of our YouTube hits data

# the MySQL is not installed by default
library('RMySQL')
mysql <- MySQL()
youtube_conn <- dbConnect(mysql, user='root', dbname='youtube_views', host='localhost')
vid_views <- fetch(dbSendQuery(youtube_conn, "SELECT date, sum(views) as views FROM hits WHERE video = 'xv9yFkEP6qI' GROUP BY date"), n=-1)
plot(as.POSIXct(vid_views$date), cumsum(vid_views$views), type='l', ylab='Cumulative Views')
