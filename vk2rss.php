<?php
	# http://exlab.driven.ru/vk2rss/public29559271
	# RewriteRule ^([a-z0-9_-]+)/?$ vk2rss.php?id=$1 [NC,L]
	# из Вконтактега в RSS
	$url = 'https://api.vk.com/method/utils.resolveScreenName';
	if (!isset($_GET['id']) or false == ($r = file_get_contents($url . '?screen_name=' . $_GET['id']))) die('failed');

	if (!($r = json_decode($r, true)) or !isset($r['response'])) die('failed');
	if (!$r['response']) die('invalid name');

	$id = $r['response']['object_id'];
	$id = $r['response']['type'] == 'user' ? $id : (0 - $id);

	$r = file_get_contents('https://api.vk.com/method/wall.get?owner_id=' . $id) or die('failed');
	$r = json_decode($r, true) or die('failed');

	if (isset($r['response'])) {
		$u = [];
		$keys = ['src', 'src_big'];

		foreach ($r['response'] as $r) {
			if (!is_array($r) or isset($r['is_pinned'])) continue;

			$data = [];
			$data['url']   = 'http://vk.com/wall' . $r['to_id'] . '_' . $r['id'];
			$data['text']  = preg_replace("#(http|https)://([A-z0-9./-]+)#", '<a href="$0">$0</a>', $r['text']);
			$data['date']  = $r['date'];
			$data['photo'] = null;

			if (isset($r['attachment']) and $r['attachment']['type'] == 'photo' and isset($r['attachment']['photo'])) {
				$r = $r['attachment']['photo'];
				foreach ($keys as $key) {
					if (isset($r[$key])) $data['photo'] = $r[$key];
				}
			}

			if ($data['photo']) {
				$data['text'] .= '<p><img src="' . $data['photo'] . '"></p>';
			}

			$u[] = $data;
		}

		$rss   = [];
		$rss[] = '<?xml version="1.0" encoding="utf-8"?>';
		$rss[] = '<rss version="2.0">';
		$rss[] = '<channel>';
		$rss[] = '<title>' . $_GET['id'] . '</title>';
		$rss[] = '<link>http://vk.com/' . $_GET['id'] . '</link>';
		$rss[] = '<pubDate>' . date('r') . '</pubDate>';
		$rss[] = '<lastBuildDate>' . date('r') . '</lastBuildDate>';
		$rss[] = '<category>news</category>';
		$rss[] = '<generator>vk2rss</generator>';
		$rss[] = '<ttl>60</ttl>';
		foreach ($u as $r) {
			$rss[] = '<item>';
			$rss[] = '<link>' . $r['url'] . '</link>';
			$rss[] = '<guid>' . $r['url'] . '</guid>';
			$rss[] = '<description><![CDATA[' . $r['text'] . ']]></description>';
			$rss[] = '<pubDate>' . date('r', $r['date']) . '</pubDate>';
			$rss[] = '</item>';
		}
		$rss[] = '</channel>';
		$rss[] = '</rss>';

		header('Content-Type: application/rss+xml; charset=utf-8');
		echo implode('', $rss);
	} else if (isset($r['error'])) {
		die($r['error']['error_msg']);
	} else {
		die('failed');
	}