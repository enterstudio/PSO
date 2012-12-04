<?php
// Target URLs to scrape
$urls = array(
	'http://codepad.viper-7.com/',
	'http://www.amazon.com/',
	'http://www.rackspace.com/',
	'http://www.youtube.com/',
	'http://www.news.com.au/',
	'http://www.google.com/',
	'http://www.bing.com/',
	'http://www.slashdot.org/',
	'http://www.wikipedia.org/',
	'http://www.php.net/'
);

include_once '../PSO.php';

$content = array();
$pool = new PSO_HTTPClient();

// Set the user agent so remote sites don't think we're a bot
$pool->userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) Gecko/20100101 Firefox/16.0';

// Add the main page to the queue
$pool->addTargets($urls, function() use (&$content) {
	// Store the response in the result array
	$content[$this->requestURI]['document'][0] = $this->responseBody;
	
	$dom = $this->getDOM();
	$base = $this;
	
	$fetch = array('img' => 'src', 'script' => 'src', 'link' => 'href');
	
	foreach($fetch as $tagname => $attribute) { 
		foreach($dom->getElementsByTagName($tagname) as $link) {
			if($href = $link->getAttribute($attribute)) {
				$target = $this->getMediaURL($href);

				$this->pool->addTarget($target, function() use (&$content, $base, $link) {
					
					if($link->getAttribute('type') == 'text/css') {
						preg_match_all('/url\s*\(?\s*["\']([^"\']+?)["\']/', $this->responseBody, $matches, PREG_SET_ORDER);
						
						foreach($matches as $match) {
							$target = $base->getMediaURL($match[1]);
							
							$this->pool->addTarget($target, function() use (&$content, $base) {
								$content[$base->requestURI]['import'][$this->requestURI] = $this->responseBody;
							});
						}
					}
					
					$content[$base->requestURI][$link->tagName][$this->requestURI] = $this->responseBody;
				});
			}
		}
	}
});

$start = microtime(true);
PSO::drain($pool);
$end = microtime(true);
$time = number_format($end - $start, 3);
$total = 0;

?>
<table width="600">
	<thead>
		<tr>
			<th>URL</th>
			<th>Size</th>
			<th>JS</th>
			<th>CSS</th>
			<th>CSS URL</th>
			<th>Images</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($content as $baseurl => $links) {
				$links += ['document'=>[],'script'=>[],'link'=>[],'import'=>[],'img'=>[]];
				$html = array_sum(array_map('strlen', $links['document']));
				$js = array_sum(array_map('strlen', $links['script']));
				$css = array_sum(array_map('strlen', $links['link']));
				$import = array_sum(array_map('strlen', $links['import']));
				$img = array_sum(array_map('strlen', $links['img']));
				$total += $html + $js + $css + $img;
		?>
			<tr>
				<td><?= htmlentities(substr($baseurl,0,50)); ?></td>
				<td><?= PSO::divideSize($html) ?>b</td>
				<td><?= PSO::divideSize($js) ?>b</td>
				<td><?= PSO::divideSize($css) ?>b</td>
				<td><?= PSO::divideSize($import) ?>b</td>
				<td><?= PSO::divideSize($img) ?>b</td>
			</tr>
		<?php } ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2"><?= PSO::divideSize($total); ?>b total</td>
			<td colspan="3"><?= $pool->requestCount ?> requests took <?= $time ?> seconds</td>
		</tr>
	</tfoot>
</table>
