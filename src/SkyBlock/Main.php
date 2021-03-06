<?php

namespace SkyBlock;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\plugin\PluginBase as Base;
use pocketmine\block\Dirt;
use pocketmine\block\Sand;
use pocketmine\block\Grass;
use pocketmine\block\Wood;
use pocketmine\level\generator\object\Tree;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\block\Sapling;
use pocketmine\utils\Random;

class Main extends Base implements Listener{
	public function onEnable(){
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!(is_dir($this->getDataFolder()."Players/"))){
			@mkdir($this->getDataFolder()."Players/");
		}
		if(!(is_dir($this->getDataFolder()."Islands/"))){
			@mkdir($this->getDataFolder()."Islands/");
		}
		$this->getLogger()->info(TextFormat::GREEN . "Listo! Mod por TKRT!");
	}
	public function onDisable(){
		$this->getLogger()->info(TextFormat::RED . "SkyBlock Deshabilitado");
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(strtolower($command->getName()) == "is"){
			if(!(isset($args[0]))){
				$sender->sendMessage(TextFormat::YELLOW . "No pusiste un subcomando");
				$sender->sendMessage(TextFormat::GREEN . "Usa: " . TextFormat::RESET . "/is help");
				return true;
			}elseif(isset($args[0])){
				if($args[0] == "help"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.help")){
						if(!(isset($args[1])) or $args[1] == "1"){
							$sender->sendMessage(TextFormat::GREEN . "[Skyblock] Enseñando lista de Ayuda");
							$sender->sendMessage(TextFormat::GREEN . "/is help");
							$sender->sendMessage(TextFormat::GREEN . "/is create");
							$sender->sendMessage(TextFormat::GREEN . "/is home");
							$sender->sendMessage(TextFormat::GREEN . "/is sethome");
							$sender->sendMessage(TextFormat::GREEN . "/is find (Solo Op)");
							return true;
						}elseif($args[1] == "2"){
							$sender->sendMessage("Pronto habra mas Comandos");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "[SkyBlock] No puedes ver el menu de ayuda");
						return true;
					}
				}elseif($args[0] == "create"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.create")){
						$senderIs = $this->getDataFolder()."Islands/".$sender->getName().".txt";
						if($sender->getLevel()->getName() == $this->getConfig()->get("Lobby")){
							$sender->sendMessage(TextFormat::YELLOW."[Skyblock] No Puedes Hacer una isla en Spawn, desgraciado :L");
							return true;
							
						}else{
							if(!(file_exists($senderIs))){
								$this->makeIsland($sender->getName());
								return true;
							}else{
								$sender->sendMessage(TextFormat::YELLOW . "[Skyblock] Tu ya tienes una isla!");
								return true;
							}
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "[Skyblock] Tu No Puedes Crear una isla!");
						return true;
					}
				}elseif($args[0] == "home"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.home")){
						if(!(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt"))){
							$sender->sendMessage("You don't have an island. Use /is create to make one");
							return true;
						}else{
							$level = $this->getServer()->getLevelByName(yaml_parse_file($this->getDataFolder()."Players/".$sender->getName().".txt"));
							if($level !== null){
								$sender->sendMessage(TextFormat::GREEN."Teleporting to your island...");
								if($sender->getLevel()->getName() !== $level->getName()){
									$sender->sendMessage(TextFormat::RED."[Skyblock] Tu no estas en el mismo mundo de tu isla. Usa ".TextFormat::YELLOW."/mw tp ".$level->getName().TextFormat::RESET." E intenta de nuevo");
									return true;
								}else{
									$sender->teleport(new Vector3(yaml_parse_file($this->getDataFolder()."Islands/".$sender->getName().".txt")));
									$sender->sendMessage(TextFormat::GREEN."[Skyblock] Listo!");
									return true;
								}
							}else{
								$sender->sendMessage(TextFormat::RED."[Skyblock] Un Error a ocurrido.");
								return true;
							}
						}
					}else{
						$sender->sendMessage(TextFormat::ORANGE."[Skyblock] No Tienes permisos para hacer eso");
						return true;
					}
				}elseif($args[0] == "find"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.find")){
						if(isset($args[1])){
							$p = $sender->getServer()->getPlayer($args[1]);
							if($p instanceof Player){
								$name = $p->getName();
								if(file_exists($this->getDataFolder()."Islands/".$name.".txt")){
									$sender->sendMessage("The coords for ".$name."'s island are");
									$sender->sendMessage(file_get_contents($this->getDataFolder()."Islands/".$name.".txt"));
									$sender->sendMessage(file_get_contents($this->getDataFolder()."Players/".$name.".txt"));
									return true;
								}else{
									$sender->sendMessage(TextFormat::YELLOW."[Skyblock] ".$name . " No Tiene una Isla");
									return true;
								}
							}elseif(file_exists($this->getDataFolder()."Islands/".$args[1].".txt")){
								$sender->sendMessage("The coords for ".$args[1]."'s island are");
								$sender->sendMessage(file_get_contents($this->getDataFolder()."Islands/".$args[1].".txt"));
								$sender->sendMessage("in world ". file_get_contents($this->getDataFolder()."Players/".$args[1].".txt"));
								return true;
							}
						}else{
							$sender->sendMessage(TextFormat::YELLOW . "[Skyblock] Necesitas especificar un Jugador");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::YELLOW . "[Skyblock] Tu no Puedes encontrar la s coordenadas de la isla de un jugador");
						return true;
					}
				}elseif($args[0] == "delete"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.delete")){
						if(!(isset($args[1]))){
							$sender->sendMessage(TextFormat::RED."[Skyblock] Estas Seguro? Usa /is delete yes para confirmar");
							return true;
						}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt")){
									unlink($this->getDataFolder()."Islands/".$sender->getName().".txt");
									$sender->sendMessage(TextFormat::BLUE."[Skyblock] Tu Isla a sido eliminada!");
									return true;
								}else{
									$sender->sendMessage(TextFormat::YELLOW."[Skyblock] Tu no Tienes una isla!");
									return true;
								}
							}elseif($args[1] == "no"){
								$sender->sendMessage(TextFormat::GRAY."[Skyblock] Ok, No eliminaremos tu isla, De todos modos nos vale queso :D");
								return true;
							}else{
								return false;
							}
						}else{
							$sender->sendMessage(TextFormat::RED."[Skyblock] Tu No puedes eliminar tu Isla");
							return true;
						}
					}elseif($args[0] == "sethome"){
						if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.sethome")){
							if(!(isset($args[1]))){
								$sender->sendMessage(TextFormat::RED."[Skyblock] Estas seguro? Asegurate que estas en tu isla");
								$sender->sendMessage(TextFormat::RED."[Skyblock] Tu Isla se perdera si no estas en tu isla. Pon /is sethome yes para confirmar");
								return true;
							}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt")){
									$sender->sendMessage("[Skyblock] Setting your home...");
									$file = $this->getDataFolder()."Islands/".$sender->getName().".txt";
									unlink($file);
									$newFile = fopen($file, "w");
									fwrite($newFile, $sender->x.", ".$sender->y.", ".$sender->z);;
									$sender->sendMessage(TextFormat::GREEN."[Skyblock] Pones Home en el lugar que quieres.");
									return true;
								}else{
									$sender->sendMessage(TextFormat::RED."[Skyblock] TU no tienes una isla!");
									return true;
								}
							}elseif($args[1] == "no"){
								$sender->sendMessage(TextFormat::BLUE."[Skyblock] Ok, no haremos eso....");
								return true;
							}else{
								$sender->sendMessage(TextFormat::GRAY."[Skyblock] Comando Desconosido: ".$args[1]);
								$sender->sendMessage(TextFormat::GREEN."[Skyblock] /sethome <yes | no>");
								return true;
							}
						}else{
							$sender->sendMessage(TextFormat::RED."[Skyblock] No tienes permiso para Poner Home.");
							return true;
						}
					}
				}
			}
		}
	
	public function makeIsland($name){
		
		$player = $this->getServer()->getPlayer($name);
		if(!($player instanceof Player)){
			return TextFormat::RED."[Skyblock] Error: Jugador no Encontrado";
		}else{
			// Make a file for the island
			@mkdir($this->getDataFolder()."Islands/".$name.".txt");
			$level = new Config($this->getDataFolder()."Islands/".$name.".txt", Config::ENUM);
			$level->set("location", $level.",".$x.",".$y.",".$z);
			$level->setAll($level->getAll());
			$level->save();

			//get pos

			$pos = explode(",", $level->get("location"));
			$level = $pos[0];
			$x = $pos[1];
			$y = $pos[2];
			$z = $pos[3];

			//set block

			$level = $this->getServer()->getLevelByName($level);
			
			// Top layer of the island
			
			// 1st side
			$level->setBlock(new Position($x, $y, $z, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+6, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// 2nd side
			$level->setBlock(new Position($x+5, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+5, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+5, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+5, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+5, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+5, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// 3rd side 
			$level->setBlock(new Position($x+4, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+4, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+4, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+4, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+4, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+4, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// 4th side
			$level->setBlock(new Position($x+3, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+3, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+3, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+3, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+3, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+3, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// 5th side	
			$level->setBlock(new Position($x+2, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+2, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+2, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+2, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+2, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+2, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// 6th side
			$level->setBlock(new Position($x+1, $y, $z+6, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+1, $y, $z+5, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+1, $y, $z+4, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+1, $y, $z+3, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+1, $y, $z+2, $level), Block::get(Block::GRASS));
			$level->setBlock(new Position($x+1, $y, $z+1, $level), Block::get(Block::GRASS));
			
			// Middle layer of the island
			
			// 1st Side 
			$level->setBlock(new Position($x+6, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+6, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+6, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+6, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+6, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+6, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			// 2nd side
			$level->setBlock(new Position($x+5, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+5, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+5, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+5, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+5, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+5, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			// 3rd side
			$level->setBlock(new Position($x+4, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+4, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+4, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+4, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+4, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+4, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			// 4th side
			$level->setBlock(new Position($x+3, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+3, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+3, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+3, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+3, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+3, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			// 5th side
			$level->setBlock(new Position($x+2, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+2, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+2, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+2, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+2, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+2, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			// 6th side
			
			$level->setBlock(new Position($x+1, $y-1, $z+6, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+1, $y-1, $z+5, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+1, $y-1, $z+4, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+1, $y-1, $z+3, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+1, $y-1, $z+2, $level), Block::get(Block::SAND));
			$level->setBlock(new Position($x+1, $y-1, $z+1, $level), Block::get(Block::SAND));
			
			
			
			// Bottom layer of the island
			
			// 1st side
			$level->setBlock(new Position($x+6, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+6, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+6, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+6, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+6, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+6, $y-1, $z+1, $level), Block::get(Block::DIRT));
			
			// 2nd side
			$level->setBlock(new Position($x+5, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+5, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+5, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+5, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+5, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+5, $y-1, $z+1, $level), Block::get(Block::DIRT));
			
			
			// 3rd side
			$level->setBlock(new Position($x+4, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+4, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+4, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+4, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+4, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+4, $y-1, $z+1, $level), Block::get(Block::DIRT));

			// 4th side
			$level->setBlock(new Position($x+3, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+3, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+3, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+3, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+3, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+3, $y-1, $z+1, $level), Block::get(Block::DIRT));
			
			// 5th side
			$level->setBlock(new Position($x+2, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+2, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+2, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+2, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+2, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+2, $y-1, $z+1, $level), Block::get(Block::DIRT));
			
			// 6th side
			$level->setBlock(new Position($x+1, $y-1, $z+6, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+1, $y-1, $z+5, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+1, $y-1, $z+4, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+1, $y-1, $z+3, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+1, $y-1, $z+2, $level), Block::get(Block::DIRT));
			$level->setBlock(new Position($x+1, $y-1, $z+1, $level), Block::get(Block::DIRT));
			
			// Tree
			$type = 0;
			Tree::growTree($level, $x+6, $x+1, $x+6, new Random(mt_rand()), Sapling::OAK);
			
			// Teleport the player to their new island
			$player->teleport(new Position($randX, $randY+5, $randZ, $this->getServer()->getLevelByName($levelName)));
			$player->sendMessage(TextFormat::GREEN . "[Skyblock] Bienvenido a tu Isla");
			$player->sendMessage(TextFormat::GREEN . "[Skyblock] Si tu Isla No se Creo,");
			$player->sendMessage(TextFormat::GREEN . "[Skyblock] Usa /is delete");
			$player->sendMessage(TextFormat::GREEN . "[Skyblock] Y create otra ;D");
			
			// Give the player a starter kit
			
			// String
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			
			// Emerald
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			
			// Sapling
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			
			// Water
			$player->getInventory()->addItem(Item::get(8));
			$player->getInventory()->addItem(Item::get(8));
			
			// Lava
			$player->getInventory()->addItem(Item::get(10));
			
			// Seeds
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			
			// Melon seeds
			$player->getInventory()->addItem(Item::get(362));
			
			// Cactus
			$player->getInventory()->addItem(Item::get(81));
			
			// Iron
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			
			// Chest
			$player->getInventory()->addItem(Item::get(54));
			
			$this->getLogger()->info(TextFormat::BLUE."[Skyblock] ".$name . TextFormat::YELLOW . " Hiso una isla");
		}
	}
	
	// When a player joins
	public function onPlayerJoinEvent(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(file_exists($this->getDataFolder()."Players/".$player->getName().".txt")){
			$player->sendMessage(TextFormat::BLUE."[Skyblock] Bienvenido Otra vez!, " . $player->getName());
			//$player->sendMessage(TextFormat::BLUE."[Skyblock]" .$msgwelcomeback " . $player->getName());
			// testing out MultiLanguage support
		}else{
			$this->getServer()->broadcastMessage(TextFormat::BLUE . "[Skyblock] Bienvenido ".TextFormat::RESET.$player->getName().TextFormat::GREEN." Al Servidor");
			$file = fopen($this->getDataFolder()."Players/".$player->getName().".txt", "w");
			fclose($file);
		}
	}
}
