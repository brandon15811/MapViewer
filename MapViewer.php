<?php

/*
__PocketMine Plugin__
name=Map Viewer
version=0.0.1
author=brandon15811
class=MapViewer
apiversion=7,8
*/

class MapViewer implements Plugin
{
	private $api;
	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
	}

	public function init()
	{
	    $this->api->event("player.offline.get", array($this, "eventHandler"));
		$this->api->event("player.move", array($this, "eventHandler"));
		$this->api->event("player.quit", array($this, "eventHandler"));

		$this->api->addHandler("server.unknownpacket", array($this, "commandHandler"), 50);
	}

	public function __destruct()
	{

	}

	public function dump($datavar)
	{
	    foreach ($datavar as $key => $value)
        {
            console($key.":".gettype($value)."\n");
        }
	}

    public function commandHandler($data, $event)
    {
        switch($event)
        {
            case "server.unknownpacket":
                console(substr($data["raw"], 0, 2));
                if (substr($data["raw"], 0, 2) !== "MV")
                {
                    return;
                }
                $players = Array("event" => "server.unknownpacket");
                $players['players'] = Array();
                $json = json_decode(substr($data['raw'], 2), true);
                switch ($json['event'])
                {
                    case "getAllPlayers":
                        foreach ($this->api->player->getAll() as $player)
                        {
                            $players['players'][$player->entity->name] = Array();
                            $playerArray = $players['players'][$player->entity->name];
                            $players['players'][$player->entity->name]['x'] = $player->entity->x;
                            $players['players'][$player->entity->name]['y'] = $player->entity->y;
                            $players['players'][$player->entity->name]['z'] = $player->entity->z;
                            print_r($playerArray);
                        }
                        print_r($players['players']);

                        ServerAPI::request()->send(0, json_encode($players), true, "127.0.0.1",
                            9614);
                }
                break;
        }
    }
    public function eventHandler($data, $event)
    {
        switch($event)
        {
            case "player.offline.get":
                ServerAPI::request()->send(0, json_encode(array(
                    "event" => "player.offline.get",
                    "x" => $data->get('position')['x'],
                    "y" => $data->get('position')['y'],
                    "z" => $data->get('position')['z'],
                    "player" => $data->get('caseusername'))), true, "127.0.0.1",9614);
                break;

            case "player.move":
                ServerAPI::request()->send(0, json_encode(array(
                    "event" => "player.move",
                    "x" => $data->x,
                    "y" => $data->y,
                    "z" => $data->z,
                    "player" => $data->name)), true, "127.0.0.1", 9614);
                break;

            case "player.quit":
                ServerAPI::request()->send(0, json_encode(array(
                    "event" => "player.quit",
                    "player" => $data->entity->name)), true, "127.0.0.1", 9614);
                break;
        }
    }
}
?>
