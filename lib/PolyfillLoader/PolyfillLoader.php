<?php
namespace PolyfillLoader;

class PolyfillLoader
{
	private $arPolyfill = [];
	private $arAlias = [];
	private $arFeature = ['default-3.6'];
	private $arAdded = [];
	private $basePath = null;
	private $minify = false;
	private $flags = [];
	private $callback = null;

	public function __construct($path = null, $minify, $flags = [])
	{
		if (!$path) {
			$path = DOC_ROOT . '/assets/js/polyfills/__dist';
		}
		$this->basePath = rtrim(strtr($path, '\\', '/'), '/');
		$this->minify = $minify;
		$this->flags = array_merge($flags);

		$directory = new \RecursiveDirectoryIterator($this->basePath);
		$filter = new FilenameFilterIterator($directory, '/meta.json$/');
		$iterator = new \RecursiveIteratorIterator($filter);
		$pos = strlen($this->basePath) + 1;

		foreach ($iterator as $file) {
			$filePath = strtr($file->getPathname(), '\\', '/');
			$data = array_merge(['dependencies' => [], 'aliases' => []], json_decode(file_get_contents($filePath), true));
			$key = substr($filePath, $pos, -10);
			$key = strtr($key, '/', '.');

			if (isset($data['aliases'])) {
				foreach ($data['aliases'] as $alias) {
					$this->arAlias[$alias][] = $key;
				}
			}

			$this->arPolyfill[$key] = $data;
		}
	}

	public function load($features)
	{
		$this->arFeature = $features;
		$this->arAdded = [];

		foreach ($this->arFeature as $feature) {
			$tmp = explode('|', $feature);
			$feature = array_shift($tmp);
			$flags = array_merge($this->flags, $tmp);
			$flags = ['gated' => in_array('gated', $flags), 'always' => in_array('always', $flags)];

			if (isset($this->arPolyfill[$feature])) {
				$this->add($feature, $flags);
			} elseif (isset($this->arAlias[$feature])) {
				foreach ($this->arAlias[$feature] as $polyfill) {
					$this->add($polyfill, $flags);
				}
			}
		}
	}

	public function setCallback($callback)
	{
		if ($callback && preg_match('/^[A-Za-z0-9_.$]+$/', $callback)) {
			$this->callback = $callback;
		} else {
			$this->callback = null;
		}
	}

	public function generate()
	{
		header('Content-Type: application/javascript');

		if ($this->minify) {
			echo '/* Disable minification (remove `.min` from URL path) for more info */' . PHP_EOL . PHP_EOL;
			$lineBreak = '';
			$lineBreakLong = '';
			echo '(function(undefined) {';
		} else {
			echo '/* Polyfill service' . PHP_EOL;
			echo ' * For detailed credits and licence information see https://github.com/financial-times/polyfill-service.' . PHP_EOL;
			echo ' * ' . PHP_EOL;
			// echo ' * UA detected: other/0.0.0 (unknown/unsupported; using policy `unknown=polyfill`)' . PHP_EOL;
			echo ' * Features requested: ' . implode(',', $this->arFeature) . PHP_EOL;
			echo ' * ' . PHP_EOL;

			foreach ($this->arAdded as $fill) {
				echo ' * - ' . $fill['name'] . ', License: ' . (isset($fill['data']->license) ? $fill['data']->license : 'CC0') . ($fill['required_by'] ? ' (required by "' . implode($fill['required_by'], '", "') . '")' : '') . PHP_EOL;
			}
			echo PHP_EOL;
			echo '*/' . PHP_EOL;
			echo '(function(undefined) {' . PHP_EOL . PHP_EOL;
			$lineBreak = PHP_EOL;
			$lineBreakLong = PHP_EOL . PHP_EOL;
		}

		$i = 0;
		// exit;

		foreach ($this->arAdded as $idx => $fill) {
			// $data = json_decode(file_get_contents("{$this->basePath}/$fill/meta.json"));
			if ($this->minify) {
				if ($fill['gated'] && $fill['data']->detectSource) {
					echo "if (!({$fill['data']->detectSourceMinified})) {";
				}
				echo file_get_contents("{$this->basePath}/{$fill['name']}/min.js");
				if ($fill['gated'] && $fill['data']->detectSource) {
					echo '}';
				}
			} else {
				// echo "log('{$fill['name']}');" . PHP_EOL;
				// echo "try {" . PHP_EOL;
				if ($fill['gated'] && $fill['data']->detectSource) {
					echo "if (!({$fill['data']->detectSource})) {";
				}
				echo preg_replace('/^/m', "\t", file_get_contents("{$this->basePath}/{$fill['name']}/raw.js")) . PHP_EOL;
				if ($fill['gated'] && $fill['data']->detectSource) {
					echo '}' . PHP_EOL . PHP_EOL;
				}
				// echo "log('{$fill['name']}');" . PHP_EOL;
				// echo "}catch(e){log('ERROR' + e);};" . PHP_EOL . PHP_EOL;
			}
		}
		// echo "log(typeof window.Promise);\n";
		// echo "alert(typeof window.requestAnimationFrame);\n";
		echo "}).call('object' === typeof window && window || 'object' === typeof self && self || 'object' === typeof global && global || {});" . PHP_EOL;

		if ($this->callback) {
			if (!$this->minify) {
				echo PHP_EOL;
			}
			echo "typeof {$this->callback}==='function' && {$this->callback}();";
		}
		// echo "log('Callback {$this->callback}');";
	}

	private function add($polyfill, $flags)
	{
		foreach ($this->arPolyfill[$polyfill]['dependencies'] as $dependency) {
			// if (!in_array($dependency, $this->arAdded)) {
			// 	$this->arAdded[] = [
			// 		'name' => $dependency,
			// 		'data' => json_decode(file_get_contents("{$this->basePath}/$fill/meta.json")),
			// 		'gated' => $flags['gated'],
			// 		'always' => $flags['always'],
			// 	];
			// }
			$this->add($dependency, $flags);
			$this->arAdded[$dependency]['required_by'][] = $polyfill;
		}
		if (!isset($this->arAdded[$polyfill])) {
			$this->arAdded[$polyfill] = [
				'name' => $polyfill,
				'data' => json_decode(file_get_contents("{$this->basePath}/$polyfill/meta.json")),
				'gated' => $flags['gated'],
				'always' => $flags['always'],
				'required_by' => [],
			];
		}
	}
}
