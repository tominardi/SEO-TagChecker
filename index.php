<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr-fr">
	<head profile="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>SEO EXTRACTOR</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
    </head>
    <body>
<?php

function getDomValue($path, $html){
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	$xp = new DOMXPath($dom);
	$nodeList = $xp->query($path);
	$output = Array();
	foreach($nodeList as $domElement){
		$output[] = $domElement->nodeValue;
	}
	return $output;
}

function __outputCSV($vals, $key, $filehandler) {
        fputcsv($filehandler, $vals, ';', '"');
}

function getUrl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 15); // times out after Ns
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		$res = curl_exec( $ch );
		$info = curl_getinfo($ch);

		$pattern = "#META http-equiv=['|\"]Refresh['|\"] content=['|\"][0-9]+;url=(.*?)['|\"]#si";
		if(preg_match($pattern, $res, $loc)) {
			$url = $loc[1];
		}else{
			$url = $info['url'];
		}
		/*if(stristr($theRes, 'XML-RPC server accepts POST requests only.'))*/ return array($res, $info, $url);
}

if(isset($_POST['txt'])){
        if($_POST['campagnName'] != '')
            $campagnName = $_POST['campagnName'];
	else
	    $campagnName = 'default';
	$tab = preg_split('#(\n)#',$_POST['txt']);
	$results = array(array('url','source','support','mot clé','contenu','nom de la campagne','Google URL Builder','nb car'));
	if($_POST['gaChecker']) $results[0][]='GA';
	if($_POST['piwikChecker']) $results[0][]='Piwik';
	
	foreach($tab as $u){
		$u = trim($u);
		if($u != ''){
		        $retour = Array();
			$proc = getUrl($u);
			//print_r($proc[1]);
			$html = $proc[0];
			$seo = array();		
			$seo['url'] = $u;
			$seo['title'] = getDomValue('//title', $html);
			$seo['h1'] = getDomValue('//h1', $html);
			$seo['h2'] = getDomValue('//h2', $html);
			$seo['h3'] = getDomValue('//h3', $html);
			$seo['h4'] = getDomValue('//h4', $html);
			$seo['h5'] = getDomValue('//h5', $html);
			$seo['h6'] = getDomValue('//h6', $html);
			$seo['strong'] = getDomValue('//strong', $html);
			$seo['a_rel_nofollow'] = getDomValue('//a[rel=nofollow]', $html);
			$seo['robots'] = getDomValue('//meta[@name="robots"]/@content', $html);
			$seo['keywords'] = getDomValue('//meta[@name="keywords"]/@content', $html);
			$seo['description'] = getDomValue('//meta[@name="description"]/@content', $html);
			
			if($_POST['gaChecker']) {
			    $seo['GA'] = 'none';
			    if(strpos($html, '_gaq.push([\'_setAccount\',') !== FALSE OR
			       strpos($html, 'new gweb.analytics.AutoTrack') !== FALSE OR
			       strpos($html, 'pageTracker._trackPageview();') !== FALSE){
				$seo['GA'] = 'OK';
			    }
			}
			
			if($_POST['piwikChecker']) {
			    $seo['Piwik'] = 'none';
			    if(strpos($html, 'piwikTracker.enableLinkTracking();')){
				$seo['Piwik'] = 'OK';
			    }
			}
			$recolte = Array('title', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'robots', 'keywords', 'description', 'strong', 'a_rel_nofollow' );
			foreach($recolte as $key) {
			foreach($seo[$key] as $value) {
			    $toAdd = Array(
				$seo['url'],
				'direct',
				'none',
				preg_replace('`( | |&nbsp;)+`i', ' ', preg_replace("(\r\n|\n|\r)",'',$value)),
				$key,
				$campagnName,
				$seo['url'].'?utm_source=direct&utm_medium=seo&utm_campagn='.urlencode($campagnName).'&utm_term='.urlencode(preg_replace('`( | |&nbsp;)+`i', ' ', preg_replace("(\r\n|\n|\r)",'',$value))).'&utm_content='.urlencode($key),
				''
				);
			    if ($_POST['gaChecker']) {
				$toAdd[] = $seo['GA'];
			    }
			    if ($_POST['piwikChecker']) {
				$toAdd[] = $seo['Piwik'];
			    }
			    $retour[] = $toAdd;
			}
			}
			foreach($retour as $push) {
			    $results[] = $push;
			}
		}
	}
        $response .= '<a href="export/'.$campagnName.'.csv">Resultat en CSV</a>';
	$response .= '<pre>';
	$response .= print_r($results, true);
	$response .= '</pre>';
	//header("Content-Type: text/csv");
	$fp = fopen('export/'.$campagnName.'.csv', 'w');
    array_walk($results, '__outputCSV', $fp);

   fclose($fp);
}
?>
<div id="wrapper">
<h1>SEO EXTRACTOR</h1>
<p>Paste your URL list on the following textarea and click on «Check It!»</p>
<form  action="" method="post">
    <label for="campagnName">Nom de la campagne : </label><input type="text" name="campagnName" id="campagnName" value="<?php echo $campagnName; ?>" /><br />
    <textarea name="txt" style="width:500px;height:700px;"><?php echo $_POST['txt']; ?></textarea><br />
    Check if Google Analytics is Enabled ? <input type="checkbox" name="gaChecker" /><br />
    Check if Piwik is Enabled ? <input type="checkbox" name="piwikChecker" /><br />
    <input type="submit" value="Check It!" />
</form>
<?php echo $response; ?>
</div>
</body>
</html>

