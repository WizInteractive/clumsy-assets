<?php namespace Clumsy\Assets;

class Asset {

    public function __construct()
    {
        $this->container = new Container;
    }

	protected function on($set, $asset, $priority)
	{
		$this->container->add($set, $asset, $priority);
	}

    public function register($set, $key, $path, $v = '', $req = false)
    {
        return $this->container->register($set, $key, $path, $v, $req);
    }

    public function batchRegister($assets)
    {
    	$default = array(
    		'set'  => false,
    		'path' => false,
    		'v'	   => '',
    		'req'  => false,
    	);

		foreach ($assets as $key => $asset)
		{
			$asset = array_merge($default, (array)$asset);
	        extract($asset);

	        if (!$set || !$key || !$path)
	        {
	        	continue;
	        }

	        $this->register($set, $key, $path, $v, $req);
		}
    }
	
    public function all()
    {
    	return $this->container->assets;
    }

	public function enqueueNew($set, $key, $path, $v = '', $req = false, $priority = 25)
	{
        if ($this->register($set, $key, $path, $v, $req))
        {
            $this->enqueue($key, $priority);
        }

        return false;
	}

	public function enqueue($asset, $priority = 25)
	{
		$assets = $this->container->assets;

		if (!isset($assets[$asset]))
        {
			if (\Config::get('assets::config.silent'))
            {
                return false; // Fail silently, unless debug is on
            }
            
            throw new \Exception("Unknown asset $asset.");            
		}
		
		if (isset($assets[$asset]['req']))
        {	
			foreach((array)$assets[$asset]['req'] as $req)
            {
				$this->enqueue($req, $priority);
			}
		}

		$path = $assets[$asset]['path'];

		$v = isset($assets[$asset]['v']) ? $assets[$asset]['v'] : null;

		$this->on($assets[$asset]['set'], array('key' => $asset, 'path' => $path, 'v' => $v), $priority);
	}

	public function style($path)
	{
		return \HTML::style($path);
	}

	public function json($id, $array)
	{
        $container = $this->container->addArray('json', array($id => $array));
    }

	public function unique($id, \Closure $closure)
	{
        $container = $this->container;
        
        if (!in_array($id, $container->unique))
        {
            $this->container->unique[] = $id;

            call_user_func($closure);

            return true;
        }
        
        return false;
	}
	
	public function font($name, $weights = false)
	{
		$name = urlencode($name);
		
		if (!$weights || !is_array($weights))
        {
			$weights = array(400);
		}
		$weights = implode(',', array_map('urlencode', $weights));

        $this->enqueueNew('styles', "font.$name", "//fonts.googleapis.com/css?family=$name:$weights", null, null, 50);
	}
}
