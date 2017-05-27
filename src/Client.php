**
     * Ping interval.
     * @var int
     */
    public static $pingInte
 
     {
         $data = unserialize($data);
         $event = $data['channel'];
         $event_data = $data['data'];
         if(!empty(self::$_events[$event]))
         {
             call_user_func(self::$_events[$event], $event_data);
         }
         elseif(!empty(Client::$onMessage))
         {
             call_user_func(Client::$onMessage, $event, $event_data);
         }
         else
         {
             throw new \Exception("event:$event have not callback");
         }
     }
    
     /**
      * Ping.
      * @return void
      */
    public static function ping()
    {
        if(self::$_remoteConnection)
        {
            self::$_remoteConnection->send('');
        }
    }

    /**
     * onRemoteClose.
     * @return void
     */
    public static function onRemoteClose()
    {
        echo "Waring channel connection closed and try to reconnect\n";
        self::$_remoteConnection = null;
        self::clearTimer();
        self::$_reconnectTimer = Timer::add(1, 'Channel\Client::connect', array(self::$_remoteIp, self::$_remotePort));
    }

    /**
     * onRemoteConnect.
     * @return void
     */
    public static function onRemoteConnect()
    {
        $all_event_names = array_keys(self::$_events);
        if($all_event_names)
        {
            self::subscribe($all_event_names);
        }
        self::clearTimer();
    }

    /**
     * clearTimer.
     * @return void
     */
    public static function clearTimer()
    {
        if(self::$_reconnectTimer)
        {
           Timer::del(self::$_reconnectTimer);
           self::$_reconnectTimer = null;
        }
    }
    
    /**
     * On.
     * @param string $event
     * @param callback $callback
     * @throws \Exception
     */
    public static function on($event, $callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('callback is not callable');
        }
        self::$_events[$event] = $callback;
        self::subscribe(array($event));
    }

    /**
     * Subscribe.
     * @param string $events
     * @return void
     */
    public static function subscribe($events)
    {
         self::connect();
         $events = (array)$events;
         foreach($events as $event)
         {
             if(!isset(self::$_events[$event]))
             {
                 self::$_events[$event] = null;
             }
         }
         self::$_remoteConnection->send(serialize(array('type' => 'subscribe', 'channels'=>(array)$events)));
    }

    /**
     * Unsubscribe.
     * @param string $events
     * @return void
     */
    public static function unsubscribe($events)
    {
        self::connect();
        $events = (array)$events;
        foreach($events as $event)
        {
            unset(self::$_events[$event]);
        }
        self::$_remoteConnection->send(serialize(array('type' => 'unsubscribe', 'channels'=>$events))); 
    }

    /**
     * Publish.
     * @param string $events
     * @param mixed $data
     */
    public static function publish($events, $data)
    {
        self::connect();
        self::$_remoteConnection->send(serialize(array('type' => 'publish', 'channels'=>(array)$events, 'data' => $data)));
    }
}
