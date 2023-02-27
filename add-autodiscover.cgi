#!/usr/bin/perl

# v1.4

use Data::Dumper;
use JSON;

my $dns_type = "linode"; # either "linode" or "local". If local, this means you are using Hestia's local DNS server, so the CNAMES will be added to that instead

# check we are in the right folder (otherwise the copy of files won't work)
if ($0 ne "/installer/add-autodiscover.cgi") {
	print "Please run this from the /installer directory!\n";
	exit;
}

# we use this to store which domains have had the autodiscover setup done, so we don't do it again and waste time
if (!-d "/installer/discover-configs-done") {
	mkdir ("/installer/discover-configs-done");
}

handle();

sub handle {

	if (!$ARGV[0]) {
		helper();
		return;
	}

	my $domain = $ARGV[0]; # the domain in question
	my $username = $ARGV[1]; # who owns the account

	# just in case the username and domain are the wrong way around...lets fix!
	if (!-d "/home/$username/web/$domain") {

		print qq|BEFORE: $domain and $username \n|;
		if (-d "/home/$domain/web/$username") {
			print "Username and domain wrong way around... lets fix! \n";
			($domain,$username) = ($username,$domain);
		} else {
			print qq|Sorry, /home/$username/web/$domain doesn't exist. Please add the domain in HestaCP first!\n|;
			helper();
			exit;
		}
	}

	if (-e "/installer/discover-configs-done/$domain") {
		print "\t\tAlready done this one... \n";
	} else {

		# create the dirs first...
		`mkdir /home/$username/web/$domain/public_html/autodiscover/`;
		`mkdir /home/$username/web/$domain/public_html/mail/`;

		# copy over the auto-discover files
		`cp -fr /installer/autodiscover/* /home/$username/web/$domain/public_html/autodiscover/`;
		`cp -fr /installer/mail/* /home/$username/web/$domain/public_html/mail/`;

		# make sure we chown, otherwise they'll be owned by root!
		`cd /home/$username/web/$domain/public_html && chown -R $username:$username *`;

		# update the files so we have the right domain...
		`sed -i "s/YOURDOMAIN.COM/$domain/g" /home/$username/web/$domain/public_html/autodiscover/autodiscover.xml`;
		`sed -i "s/YOURDOMAIN.COM/$domain/g" /home/$username/web/$domain/public_html/autodiscover/autodiscover.json`;
		`sed -i "s/YOURDOMAIN.COM/$domain/g" /home/$username/web/$domain/public_html/mail/config-v1.1.xml`;

		if ($dns_type eq "linode") {

			# Grab all our domains in the Linode DNS , so we can find the ID of the domain we want to work on
			my $json = `linode-cli domains list --pretty`;
			my $domain_json = decode_json($json);

			foreach my $domain_rec (@{$domain_json}) {

				if (lc($domain_rec->{domain}) eq lc($domain)) {

					# get a list of domains on our linode DNS records for this domain
					my $dns_entries = `linode-cli domains records-list $domain_rec->{id} --pretty`;
					my $dns_json = decode_json($dns_entries);

					# delete any existing CNAME records for autodiscover and autoconfig (there shouldn't be any, unless you have run this script already!)
					foreach my $dns (@{$dns_json}) {
						if ($dns->{type} eq "CNAME") {
							if ($dns->{name} eq "autodiscover" || $dns->{name} eq "autoconfig") {
								`linode-cli domains records-delete $domain_rec->{id} $dns->{id}`;
							}

						}
					}

					# add in 2 CNAME records for autodiscover and autoconfig
					foreach my $sub (qw/autoconfig autodiscover/) {
						`linode-cli domains records-create $domain_rec->{id} --type CNAME --name $sub --target mail.$domain`;
					}

					# Create the rules.conf file...
					# I commented this out as I ended up using autodiscover.xml and not passing in username params (all my tests in Win 10 Mail didn't seem to like using the PHP script)
					#`echo "rewrite ^/autodiscover/autodiscover\.xml\$ /autodiscover/autodiscover.php last;" > /home/$username/conf/web/autodiscover.$domain/rules.conf`;

					# !!!! I got rid of this, as it seems to cause move problems than its worth!
					# add DNS records for autodiscover... hopefully these work!
					#if (`linode-cli domains records-list $domain_rec->{id}` =~ /_pop3/) {
						#print qq|Already seems to have DNS records... don't add again!\n|;
					#} else {
						# foreach (split /,/, q|imap:10:143,imaps:0:993,pop3:20:110,pop3s:10:995,smtp:10:25,smtps:0:465,submission:0:587|) {
						# 	my ($protocol,$priority,$port) = split /:/;
						# 	print qq|linode-cli domains records-create $domain_rec->{id} --type SRV --protocol tcp --target mail.$domain --priority $priority --weight 1 --port $port --service $protocol --ttl_sec 300\n|;
						# 	`linode-cli domains records-create $domain_rec->{id} --type SRV --protocol tcp --target mail.$domain --priority $priority --weight 1 --port $port --service $protocol --ttl_sec 300`;
						# }
					# 	`linode-cli domains records-create $domain_rec->{id} --type SRV --name _autodiscover._tcp --target autodiscover.$domain --priority 10 --weight 10 --port 443 --service autodiscover --ttl_sec 300`;
					# }

					# now setup the aliases on the main domain
					`v-add-web-domain-alias $username $domain autoconfig.$domain yes`;
					`v-add-web-domain-alias $username $domain autodiscover.$domain yes`;

					# rebuild the SSL using the new autoconfig/autodiscover domains
					`v-add-web-domain-ssl-force '$username' '$domain'`;

					# set to rebuild after 5 minutes (gives DNS time to propagate)
					`v-schedule-letsencrypt-domain '$username' '$domain' 'www.$domain,autodiscover.$domain,autoconfig.$domain'`;

				}


			}
		} else {

			# add to local BIND DNS server
			`v-add-dns-record $username $domain autodiscover CNAME $domain`;
			`v-add-dns-record $username $domain autoconfig CNAME $domain`;

		}

		`touch /installer/discover-configs-done/$domain`;

		# restart nginx, so the rules take effect :)
		`v-restart-service  'nginx' ''`;
	
	} else {
		print qq|Domain $domain already has autodiscover setup...\n|;
	}

	# now we should be done!!!

}

sub helper {

	print qq|Instructions:

perl add.cgi %domain% %username% %type%

%domain% - the domain you want to add
%username% - the username of who OWNS the domain
%type% - the type of Apache config file to create. Either "perl", "modperl" or "none"

If you choose mod_perl, you still need to tweak the startup.pl line in it (to pre-load your modules)

|;

}
