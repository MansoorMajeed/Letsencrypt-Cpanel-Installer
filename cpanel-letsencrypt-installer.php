<?php

# Please note that no proper validation is done in the script as I'm too lazy for that
# make sure that the domain is pointed to the server's ip correctly
# and, do it at your own risk

# Location of the letsencrypt script
$le = "/root/letsencrypt/letsencrypt-auto";
$handle = fopen("php://stdin","r");
echo "Welcome to Letsencrypt SSL Setup Script\n";
echo "Please Enter the details requested\n";
echo "Domain : ";
$domain = trim(fgets($handle));
echo "cPanel username : ";
$username = trim(fgets($handle));
echo "Email : ";
$email = trim(fgets($handle));

echo "Retrieving the SSL certificates for the domain $domain..!!\n";
$cmd = "$le --text --agree-tos --email $email certonly --renew-by-default --webroot --webroot-path /home/$username/public_html/ -d $domain";
echo "The command is: $cmd";
echo "\n\nAre you sure you wanna continue? If not, press Ctrl+C now\n";
fgets($handle);

$result = shell_exec($cmd);
echo "Command completed: \n$result\n";


echo "Setting up certificates for the domain\n";
$whmusername = 'root';
$hash = file_get_contents('/root/.accesshash');
$query = "https://127.0.0.1:2087/json-api/listaccts?api.version=1&search=$username&searchtype=user";
$curl = curl_init();
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
  
$header[0] = "Authorization: WHM $whmusername:" . preg_replace("'(\r|\n)'","",$hash);
curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
curl_setopt($curl, CURLOPT_URL, $query);
$ip = curl_exec($curl);
if ($ip == false) {
        echo "Curl error: " . curl_error($curl);
}
$ip = json_decode($ip, true);
$ip = $ip['data']['acct']['0']['ip'];
print "IP: $ip\n";

$cert = urlencode(file_get_contents("/etc/letsencrypt/live/" . $domain . "/cert.pem"));
$key = urlencode(file_get_contents("/etc/letsencrypt/live/" . $domain . "/privkey.pem"));
$chain = urlencode(file_get_contents("/etc/letsencrypt/live/" . $domain . "/chain.pem"));

$query = "https://127.0.0.1:2087/json-api/installssl?api.version=1&domain=$domain&crt=$cert&key=$key&cab=$chain&ip=$ip";
curl_setopt($curl, CURLOPT_URL, $query);
$result = curl_exec($curl);
if ($result == false) {
        echo "Curl error: " . curl_error($curl);
}
curl_close($curl);
  
print $result;


echo "All Done\n";
