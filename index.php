<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="fr-fr">
	<head profile="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>SEO EXTRACTOR</title>
		<link rel="stylesheet" type="text/css" href="style.css" />
        <script type="text/javascript" src="jquery-1.4.4.min.js"></script>
        <script type="text/javascript" src="app.main.js"></script>
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

function getDomAttribute($path, $html, $attr){
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	$xp = new DOMXPath($dom);
	$nodeList = $xp->query($path);
	$output = Array();
	foreach($nodeList as $domElement){
		$output[] = $domElement->getAttribute($attr);
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
	    
	    if($_POST['engines-only']) {
	        $tab = preg_split('#(\n)#',$_POST['txt']);
	        $results = array(array('url'));
	        if($_POST['gaChecker']) $results[0][]='GA';
	        if($_POST['piwikChecker']) $results[0][]='Piwik';
	        foreach($tab as $u) {
	            if($u != '') {
	                $retour = Array();
			        $proc = getUrl($u);
			        $html = $proc[0];
			        $retour[] = $u;
			        if($_POST['gaChecker']) {
        			    $retour['GA'] = 'none';
		        	    if(strpos($html, '_gaq.push([\'_setAccount\',') !== FALSE OR
		        	       strpos($html, 'new gweb.analytics.AutoTrack') !== FALSE OR
		        	       strpos($html, 'pageTracker._trackPageview();') !== FALSE) {
		        		$retour['GA'] = 'OK';
		       	        }
			        }
			
			        if($_POST['piwikChecker']) {
			            $retour['Piwik'] = 'none';
			            if(strpos($html, 'piwikTracker.enableLinkTracking();')) {
        		    		$retour['Piwik'] = 'OK';
		    	        }
			        }
			        //Ajout des lignes
			        $results[] = $retour;
	            }
	        }
	        //création du fichier
	        $response .= '<a href="export/'.$campagnName.'_engines.csv">Resultat en CSV</a>';
	        $response .= '<pre>';
    	    $response .= print_r($results, true);
	        $response .= '</pre>';
	        //header("Content-Type: text/csv");
	        $fp = fopen('export/'.$campagnName.'_engines.csv', 'w');
            array_walk($results, '__outputCSV', $fp);

            fclose($fp);
	    }
	    else {
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
			$seo['alt_img'] = getDomAttribute('//img', $html, 'alt');
			
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
			$recolte = Array();
			if ($_POST['title']) $recolte[] = 'title';
			if ($_POST['h1']) $recolte[] = 'h1';
			if ($_POST['h2']) $recolte[] = 'h2';
			if ($_POST['h3']) $recolte[] = 'h3';
			if ($_POST['h4']) $recolte[] = 'h4';
			if ($_POST['h5']) $recolte[] = 'h5';
			if ($_POST['h6']) $recolte[] = 'h6';
			if ($_POST['robots']) $recolte[] = 'robots';
			if ($_POST['keywords']) $recolte[] = 'keywords';
			if ($_POST['description']) $recolte[] = 'description';
			if ($_POST['strong']) $recolte[] = 'strong';
			if ($_POST['a_rel_nofollow']) $recolte[] = 'a_rel_nofollow';
			if ($_POST['alt_img']) $recolte[] = 'alt_img';
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
}
?>
<div id="wrapper">
<h1>SEO EXTRACTOR</h1>
<p>Paste your URL list on the following textarea and click on «Check It!»</p>

<form  action="" method="post">
<p class="ui-helper-clearfix"><span class="click-me"><label for="engines-only">Only check Search Engines : </label> <input type="checkbox" name="engines-only" id="engines-only" /></span></p>
<p>
    Check if Google Analytics is Enabled ? <input type="checkbox" name="gaChecker" /><br />
    Check if Piwik is Enabled ? <input type="checkbox" name="piwikChecker" /></p>
<p class="ui-helper-clearfix" id="selectors">Select element to add in your report :<br />
    <span class="click-me"><label for="title">title : </label> <input type="checkbox" name="title" id="title"  checked="checked" /></span>
    <span class="click-me"><label for="h1">h1 : </label> <input type="checkbox" name="h1" id="h1"  checked="checked" /></span>
    <span class="click-me"><label for="h2">h2 : </label> <input type="checkbox" name="h2" id="h2"  checked="checked" /></span>
    <span class="click-me"><label for="h3">h3 : </label> <input type="checkbox" name="h3" id="h3"  checked="checked" /></span>
    <span class="click-me"><label for="h4">h4 : </label> <input type="checkbox" name="h4" id="h4"  checked="checked" /></span>
    <span class="click-me"><label for="h5">h5 : </label> <input type="checkbox" name="h5" id="h5"  checked="checked" /></span>
    <span class="click-me"><label for="h6">h6 : </label> <input type="checkbox" name="h6" id="h6"  checked="checked" /></span>
    <span class="click-me"><label for="robots">robots : </label> <input type="checkbox" name="robots" id="robots"  checked="checked" /></span>
    <span class="click-me"><label for="keywords">keywords : </label> <input type="checkbox" name="keywords" id="keywords"  checked="checked" /></span>
    <span class="click-me"><label for="description">description : </label> <input type="checkbox" name="description" id="description"  checked="checked" /></span>
    <span class="click-me"><label for="strong">strong : </label> <input type="checkbox" name="strong"  id="strong" checked="checked" /></span>
    <span class="click-me"><label for="a_rel_nofollow">a_rel_nofollow : </label> <input type="checkbox" name="a_rel_nofollow" id="a_rel_nofollow"  checked="checked" /></span>
    <span class="click-me"><label for="alt_img">alt_img : </label> <input type="checkbox" name="alt_img"  id="alt_img" checked="checked" /></span>
</p>
    <label for="campagnName">Nom de la campagne : </label><input type="text" name="campagnName" id="campagnName" value="<?php echo $campagnName; ?>" /><br />
    <textarea name="txt" style="width:500px;height:700px;"><?php echo $_POST['txt']; ?></textarea>
    <input type="submit" value="Check It!" />
</form>
<?php echo $response; ?>
</div>
</body>
</html>

