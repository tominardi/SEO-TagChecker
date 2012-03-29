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
	foreach($nodeList as $domElement){
		return $domElement->nodeValue;
	}
}

function __outputCSV(&$vals, $key, $filehandler) {
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
	$tab = preg_split('#(\n)#',$_POST['txt']);
	$results = array(array('url','status','redirect','title','h1','h2','h3','robots','keywords','description','charset'));
	if($_POST['gaChecker']) $results[0][]='GA';
	if($_POST['piwikChecker']) $results[0][]='Piwik';
	
	foreach($tab as $u){
		$u = trim($u);
		if($u != ''){
			$proc = getUrl($u);
			//print_r($proc[1]);
			$html = $proc[0];
			$seo = array();		
			$seo['url'] = $u;
			$seo['status'] = $proc[1]['http_code'];
			if($u != $proc[2]) $seo['redirect'] = $proc[2];
			else  $seo['redirect'] = '';
			$seo['title'] = getDomValue('//title', $html);
			$seo['h1'] = getDomValue('//h1', $html);
			$seo['h2'] = getDomValue('//h2', $html);
			$seo['h3'] = getDomValue('//h3', $html);
			$seo['robots'] = getDomValue('//meta[@name="robots"]/@content', $html);
			$seo['keywords'] = getDomValue('//meta[@name="keywords"]/@content', $html);
			$seo['description'] = getDomValue('//meta[@name="description"]/@content', $html);
			$ch = getDomValue('//meta[contains(@content,"charset")]/@content', $html);//gere le bon encodage
			$ch = split('charset=', $ch);
			$ch = $ch[1];
			$seo['charset'] = $ch;
			
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
			
			$results[] = $seo;			
		}
	}
	$response .= '<pre>';
	$response .= print_r($results, true);
	$response .= '</pre>';
	//header("Content-Type: text/csv");
	$fp = fopen('seoextractor.csv', 'w');
    
    array_walk($results, '__outputCSV', $fp);

   fclose($fp);
   $response .= '<a href="seoextractor.csv">Resultat en CSV</a>';
}
?>
<div id="wrapper">
<h1>SEO EXTRACTOR</h1>
<p>Paste your URL list on the following textarea and click on «Check It!»</p>
<form  action="" method="post">
	<textarea name="txt" style="width:500px;height:700px;"><?php echo $rt; ?></textarea><br />
	Check if Google Analytics is Enabled ? <input type="checkbox" name="gaChecker" /><br />
	Check if Piwik is Enabled ? <input type="checkbox" name="piwikChecker" /><br />
	<input type="submit" value="Check It!" />
</form>
<?php echo $response; ?>
</div>
</body>
</html>

