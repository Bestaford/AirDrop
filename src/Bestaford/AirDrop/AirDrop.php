<?php

namespace Bestaford\AirDrop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\CallbackTask;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\block\Chest as ChestBlock;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest as ChestTile;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class AirDrop extends PluginBase {
	
	public $config;
	public $position;

	public function onEnable() {
		@mkdir($this->getDataFolder());
 		$this->saveDefaultConfig();
 		$this->config = (new Config($this->getDataFolder()."config.yml", Config::YAML))->getAll();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask(array($this, "spawnChest")), 20 * 60 * $this->config["interval"]);
	}

	public function spawnChest() {
		if(empty($this->config["items"])) {
			return;
		}
		$level = $this->getServer()->getDefaultLevel();
		if($this->position !== null) {
			$block = $level->getBlock($this->position);
			if($block instanceof ChestBlock) {
				$level->setBlock($this->position, Block::get(Block::AIR));
			}
			$tile = $level->getTile($this->position);
			if($tile instanceof ChestTile) {
				$level->removeTile($tile);
			}
		}
		$x = rand($this->config["drop_x_min"], $this->config["drop_x_max"]);
		$z = rand($this->config["drop_z_min"], $this->config["drop_z_max"]);
		if(($x > $this->config["spawn_x_min"] && $x < $this->config["spawn_x_max"]) && ($z > $this->config["spawn_z_min"] && $z < $this->config["spawn_z_max"])) {
			$distanceX = 0;
			$minX = false;
			if(($x - $this->config["spawn_x_min"]) < ($this->config["spawn_x_max"] - $x)) {
				$distanceX = ($x - $this->config["spawn_x_min"]);
				$minX = true;
			} else {
				$distanceX = ($this->config["spawn_x_max"] - $x);
			}
			$distanceZ = 0;
			$minZ = false;
			if(($z - $this->config["spawn_z_min"]) < ($this->config["spawn_z_max"] - $z)) {
				$distanceZ = ($z - $this->config["spawn_z_min"]);
				$minZ = true;
			} else {
				$distanceZ = ($this->config["spawn_z_max"] - $z);
			}
			if($distanceX < $distanceZ) {
				if($minX) {
					$x = $this->config["spawn_x_min"];
				} else {
					$x = $this->config["spawn_x_max"];
				}
			} else {
				if($minZ) {
					$z = $this->config["spawn_z_min"];
				} else {
					$z = $this->config["spawn_z_max"];
				}
			}
		}
		$y = $level->getHighestBlockAt($x, $z) + 1;
		if($y > 0 && $y < 255) {
			$chest = Tile::createTile("Chest", $level, new CompoundTag(" ", [ new ListTag("Items", []), new StringTag("id", Tile::CHEST), new IntTag("x", $x), new IntTag("y", $y), new IntTag("z", $z) ]));
 			$position = new Vector3($x, $y, $z);
			$level->setBlock($position, Block::get(Block::CHEST));
			$this->position = $position;
 			$level->addTile($chest);
			foreach($this->config["items"] as $stringId => $chance) {
				if($chance < 0) $chance = 0;
				if($chance > 100) $chance = 100;
				if(rand(0, 100) <= $chance) {
					$id = $stringId;
					$meta = 0;
					if(strpos($stringId, ":") !== false) {
						$stringId = explode(":", $stringId);
						$id = $stringId[0];
						$meta = $stringId[1];
					}
					$chest->getInventory()->addItem(Item::get($id, $meta));
				}
			}
			$message = $this->config["message"];
			$message = str_replace("{x}", $x, $message);
			$message = str_replace("{y}", $y, $message);
			$message = str_replace("{z}", $z, $message);
			$this->getServer()->broadcastMessage($message);
		} else {
			$this->spawnChest();
		}
	}
}