<?php

//set default list, so simply adding an URL-parameter "?download" will suffice
$preSelection=array("ads","hacking","music","porn","sex","spyware","violence","adv","audio-video","tracker","warez","webradio","aggressive","drugs","podcasts","radiotv","updatesites","webtv","movies");
//use selected categories or use defaults.
if(isset($_GET["c"]))
	$selectedCategories=$_GET["c"];
else
	$selectedCategories=$preSelection;

//get update time of last update.
$lastDownloadTime=getLastUpdateTime();

//if local list is older than 1 week
if($lastDownloadTime<strtotime("-1 week"))
{
	@unlink("shallalist.tar.gz");
	@unlink("shallalist.tar");
	downloadShallaList("http://www.shallalist.de/Downloads/shallalist.tar.gz");
	unpackList();
	
	$lastDownloadTime=saveDownloadTimeToFile();
}

//if downloadbutton is hit
if(isset($_GET["download"]))
{
	//generate random file
	$tempFileName="conf".mt_rand();
	
	//add each selected category to conf-file
	foreach ($selectedCategories as $category)
	{
		assembleConfFile($category,$tempFileName);
	}
	
	//deliver it as download
	header('Content-Disposition: attachment; filename="dnsmasq.conf"');
	
	//add some header information.
	echo "#######################################################################################\n";
	echo "#This file is derived from URL blacklist maintained by http://www.shallalist.de/\n";
	echo "#using an automatic conversion tool: https://github.com/derStephan/shallalistToConf\n";
	echo "#Note: The author of this tool is in no way connected to shalla.\n";
	echo "#\n";
	echo "#creation date: ".date("c")."\n";
	echo "#last list update: ".date("c",$lastDownloadTime)."\n";
	echo "#in cron use: wget \"http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]."\" -O /etc/dnsmasq.conf && /etc/init.d/dnsmasq restart\n";
	echo "#######################################################################################\n";
	readfile($tempFileName);
	
	//delete temp file
	unlink($tempFileName);
}
else
{
	//if downloadbutton is not pressed, show list.
	showAvailableCategorySelection($lastDownloadTime, $selectedCategories);
}


function showAvailableCategorySelection($lastDownloadTime, $selectedCategories)
{
	//get main categories from list
	$categoryDirectories=glob("list/BL/*");

	?>
	<!DOCTYPE html>
	<head>
		<title>Shalla to conf</title>
		<script type="text/javascript">
		function toggle(source) 
		{
			checkboxes = document.getElementsByName('c[]');
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].checked = source.checked;
			}
		}
		</script>
	
	</head>
	<body>
		
	last list update: <?=date("c",$lastDownloadTime);?>
	
	<form method="GET">
	<input type="checkbox" value="select all" onClick="toggle(this)" id="selectAll"><label for="selectAll">toggle all</label><br/><br />		
	<input type="submit" value="download conf" name="download"><br />
	<?php
	foreach ($categoryDirectories as $categoryDirectory)
	{
		if(is_dir($categoryDirectory))
		{
			$category=explode("/",$categoryDirectory);
			$category=$category[2];
			?>
			<input type="checkbox" name="c[]" value="<?=$category?>" id="<?=$category?>" <?php if(in_array($category,@$selectedCategories)) echo "checked"; ?>><label for="<?=$category?>"><?=$category?></label><br />

			<?php
		}
	}
	?>
	<input type="submit" value="download conf" name="download">
	</form>
	</body>
	</html>
	<?php
	
}


function getLastUpdateTime()
{
	if (!file_exists("lastDownload"))
		return 0;
	
	$fp=fopen("lastDownload","r");
	$lastDownloadTime=fgets($fp);
	fclose($fp);
	return $lastDownloadTime;
}



function downloadShallaList($url)
{
	// is cURL installed yet?
	if (!function_exists('curl_init')){
		die('Sorry cURL is not installed!');
	}

	// OK cool - then let's create a new cURL resource handle
	$ch = curl_init();

	// Now set some options (most are optional)
	// Set URL to download
	curl_setopt($ch, CURLOPT_URL, $url);
	// Include header in result? (0 = yes, 1 = no)
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Should cURL return or print out the data? (true = return, false = print)
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Timeout in seconds, pretty high!
	curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
	// Download the given URL, and save output
	$contents = curl_exec($ch);

	//write output to file
	$fp=fopen("shallalist.tar.gz","w");
	fwrite($fp, $contents);
	fclose($fp);

	// Close the cURL resource, and free system resources
	curl_close($ch);
	
}

function unpackList()
{
	$p = new PharData('shallalist.tar.gz');
	$p->decompress(); // creates files.tar

	// unarchive from the tar
	$phar = new PharData('shallalist.tar');
	$phar->extractTo('list',null,true); 
}

function saveDownloadTimeToFile()
{
	//save downloadtime to file.
	$fp=fopen("lastDownload","w");
	fwrite($fp, time());
	fclose($fp);
	
	return time();
}



function assembleConfFile($category,$confFilename)
{
	$directory=glob("list/BL/$category");
	
	@$directory=$directory[0];
	
	if($directory[0]=="")
		return false;
	
	if(file_exists ("$directory/domains"))
		writeDomainsToConf("$directory/domains",$confFilename);
	else 
	{
		$subCategories=glob("$directory/*");
		foreach($subCategories as $subCategory)
		{
			writeDomainsToConf("$subCategory/domains",$confFilename);
		}
	}
}

function writeDomainsToConf($filePath,$confFilename)
{
	$domains=fopen($filePath,"r");
	$conf=fopen($confFilename,"a");
	//add heading for upcoming list.
	fwrite($conf,"#category: $filePath \n");
	while($line = fgets($domains))
	{
		if(!filter_var(trim($line), FILTER_VALIDATE_IP))
			fwrite($conf,"address=/.".strtok($line,"\n")."/0.0.0.0\n");
	}
		
	fclose($conf);
	fclose($domains);
}

?>
