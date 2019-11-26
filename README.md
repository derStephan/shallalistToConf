# shallalistToConf
Generate a config file for dnsmasq from URL-blacklist of http://www.shallalist.de/

Goal is to redirect domains (and all subdomains) of selected categories to 0.0.0.0 thus these domains are blocked on DNS basis

language: PHP 5, needs cURL and PHAR

Installation: simply put php-file to your web-root and run it in browser. Note, that the script must be allowed to create files and folders in its own directory. 

When run first, the current full blacklist is downloaded: http://www.shallalist.de/Downloads/shallalist.tar.gz
The list is decompressed and saved to a subfolder.

After initial download the list will be updated if last update is older than 1 week. 

Based on downloaded list you can select the categories to be included in the dnsmasq.conf file. 

After pressing download, a dnsmasq.conf file is sent to the user.

dnsmasq.conf file looks like this:

```
#######################################################################################
#This file is derived from URL blacklist maintained by http://www.shallalist.de/
#using an automatic conversion tool: https://github.com/derStephan/shallalistToConf
#Note: The author of this tool is in no way connected to shalla.
#
#creation date: 2015-06-02T13:09:47+02:00
#last list update: 2015-06-02T11:17:01+02:00
#in cron use: wget "http://<URLofPhpFile>?download" -O /etc/dnsmasq.conf && /etc/init.d/dnsmasq restart
#######################################################################################
#category: list/BL/adv/domains 
address=/.000freexxx.com/0.0.0.0
address=/.004.frnl.de/0.0.0.0
address=/.clipsguide.com/0.0.0.0
...
```

dnsmasq.conf file and given command for cron are tested to be working in ipFire. Just drop it into /etc/dnsmasq.conf and restart dnsmasq.

Please note: resuling file may very well exceed 50 MB with 1.500.000+ lines. But blocking is pretty fast on my old hardware anyway. 

# usage with cron

You can do one of the following:

1. change the very first variable in php file to meet your requirements. Now you can run the following command in your cron: ```wget "<URLofPhpFile>?download" -O /etc/dnsmasq.conf && /etc/init.d/dnsmasq restart```
2. select your categories from the list and download the file manually. Open it in an editor. In last line of the header, there is the complete cron command for the selected categories. Just copy and paste it into fcrontab -e.

note: After downloading the conf file, dnsmasq has to be restarted. The given cron commands include this restart.

# screenshot of category selection
![Screenshot](https://cloud.githubusercontent.com/assets/7764931/7933941/5688de0c-0923-11e5-8233-3b3bfb3c5e66.png)
