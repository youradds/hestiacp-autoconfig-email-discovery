# hestiacp-autoconfig-email-discovery

I spent AGES trying to get autoconfig/autodiscover to work. It seems like they don't want to make it easy with different methods for each client, and no concensus on how things should work (for example, some people want DNS SRV records, others want autodiscover subdomains/files, others autoconfig... etc etc).

Anyway for my own stuff I wanted a quick way to add autodiscover for my own servers and domains - thus this script for setting up autoconfig/autodiscove email settings for mail servers

# It assumes:

1) Each domain needs to have mail. and webmail. subdomains for the domain, and SSL setup for the domain in question.
2) You are on a Linode server (you can easily modify it for your own server, but a lot of the code is based on linode-cli)
3) You have linode-cli installed (https://www.linode.com/docs/products/tools/cli/guides/install/). Be sure to run linode-cli and configure the API credentials (otherwise it'll just hang, and do nothing!)
4) If you want to modify, a knowledge of Perl is good (that being said - there is nothing stopping someone from converting this into a bash/PHP/etc script, and just using it as a guide!)
5) You need to create a folder called /installer at the root level, and put all these files in it
6) You need to be logged in as root
7) The domain you are adding already has SSL enabled via LetsEncrypt 

Ok so running it is pretty simple:

perl /installer/add-autodiscover.cgi domain.com user

You should see some debug. 

# What it does:

1) It checks to make sure the domain + user combination exist
2) It creates an **autoconfig** and **autodiscover** folder in the domains public_html folder
3) Copies over the demo .xml and .json files, and then runs a `sed` on them to replace the domain name with the actual domain name you are setting up
4) CHOWN's the new files and folders to the correct user account
5) Loads all your DNS records from linode (via linode-cli) and loops through until it finds the domain in question.
6) Adds in the CNAME records for **autodiscover** and **autoconfig**
7) Adds the 2 new subdomains as aliases via `v-add-web-domain-alias`
8) Runs `v-add-web-domain-ssl-force` and then `v-schedule-letsencrypt-domain` to do the magic and update the SSL records 
9) Restart nginx just to make sure the SSL certificates are updated

Thats pretty much it! I'm sure there are improvements that can be made, but I wanted to put this out there so others can hopefully make use. I would love if it were possible to have a single .xml and .json file in one location and dynamically change the domain name (i.e the `<hostname>mail.DOMAINNAME.COM</hostname>` part), but I wouldn't figure out a way to do that.

