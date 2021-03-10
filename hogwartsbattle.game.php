<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * HogwartsBattle implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * hogwartsbattle.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once('hogwartsCards.php');
require_once('villainCards.php');
require_once('darkArtsCards.php');

class HogwartsBattle extends Table
{
    private static $TRIGGER_ON_HAND = 'onHand';
    private static $TRIGGER_ON_DISCARD = 'onDiscard';
    private static $TRIGGER_ON_ACQUIRE = 'onAcquire';
    private static $TRIGGER_ON_DEFEAT_VILLAIN = 'onDefeatVillain';
    private static $TRIGGER_ON_DMG_DARK_ARTS_OR_VILLAIN = 'onDmgDarkArtsOrVillain';
    private static $TRIGGER_ON_LOCATION_TOKEN = 'onLocationToken';

    private static $SOURCE_HOGWARTS_CARD = 'hogwartsCard';
    private static $SOURCE_VILLAIN = 'villain';

	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array(
            'played_card_id' => 10,
            'play_card_option' => 11,
            'effect_id_with_option' => 12,

            'game_number' => 20,
            'location_total' => 21,
            'location_number' => 22,
            'location_marker' => 23,

            'dark_arts_cards_revealed' => 27,

            'villains_max' => 30,
            'villain_1_dmg' => 31,
            'villain_2_dmg' => 32,
            'villain_3_dmg' => 33,
            'villain_turn_slot' => 40,
            'villain_turn_id' => 41
        ) );

        $this->hogwartsCardsLibrary = new HogwartsCards();
        $this->villainCardsLibrary = new VillainCards();
        $this->darkArtsCardsLibrary = new DarkArtsCards();

        $this->hogwartsCards = self::getNew("module.common.deck");
        $this->hogwartsCards->init("hogwarts_card");

        $this->heroDecks = array(
            HogwartsCards::$harryId => self::getNew("module.common.deck"),
            HogwartsCards::$ronId => self::getNew("module.common.deck"),
            HogwartsCards::$hermioneId => self::getNew("module.common.deck"),
            HogwartsCards::$nevilleId => self::getNew("module.common.deck"),
        );
        $this->heroDecks[HogwartsCards::$harryId]->init("harry_card");
        $this->heroDecks[HogwartsCards::$harryId]->autoreshuffle = true;
        $this->heroDecks[HogwartsCards::$ronId]->init("ron_card");
        $this->heroDecks[HogwartsCards::$ronId]->autoreshuffle = true;
        $this->heroDecks[HogwartsCards::$hermioneId]->init("hermione_card");
        $this->heroDecks[HogwartsCards::$hermioneId]->autoreshuffle = true;
        $this->heroDecks[HogwartsCards::$nevilleId]->init("neville_card");
        $this->heroDecks[HogwartsCards::$nevilleId]->autoreshuffle = true;

        $this->villainCards = self::getNew("module.common.deck");
        $this->villainCards->init("villain_card");

        $this->darkArtsCards = self::getNew("module.common.deck");
        $this->darkArtsCards->init("dark_arts_card");
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "hogwartsbattle";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        $possibleHeroes = [HogwartsCards::$harryId, HogwartsCards::$ronId, HogwartsCards::$hermioneId, HogwartsCards::$nevilleId];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_hero) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            shuffle($possibleHeroes);
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."','".array_pop($possibleHeroes)."')";

        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        $gameNr = 1; // TODO comes from game options
        $villains_max = min(array($gameNr, 3));

        // Init global values with their initial values
        self::setGameStateInitialValue('played_card_id', 0);
        self::setGameStateInitialValue('play_card_option', 0);
        self::setGameStateInitialValue('effect_id_with_option', 0);

        self::setGameStateInitialValue('game_number', $gameNr);
        self::setGameStateInitialValue('location_total', count($this->locations[$gameNr]));
        self::setGameStateInitialValue('location_number', 1);
        self::setGameStateInitialValue('location_marker', 0);

        self::setGameStateInitialValue('dark_arts_cards_revealed', 0);

        self::setGameStateInitialValue('villains_max', $villains_max);
        self::setGameStateInitialValue('villain_1_dmg', 0);
        self::setGameStateInitialValue('villain_2_dmg', 0);
        self::setGameStateInitialValue('villain_3_dmg', 0);

        self::setGameStateInitialValue('villain_turn_slot', 0);
        self::setGameStateInitialValue('villain_turn_id', 0);

        $this->darkArtsCards->createCards($this->darkArtsCardsLibrary->gameCards($gameNr));
        $this->darkArtsCards->shuffle('deck');

        $this->villainCards->createCards($this->villainCardsLibrary->gameCards($gameNr));
        $this->villainCards->shuffle('deck');
        $this->villainCards->pickCard('deck', 1);
        if ($villains_max >= 2) {
            $this->villainCards->pickCard('deck', 2);
        }
        if ($villains_max >= 3) {
            $this->villainCards->pickCard('deck', 3);
        }

        $this->hogwartsCards->createCards($this->hogwartsCardsLibrary->game1Cards(), 'deck');
        $this->hogwartsCards->shuffle('deck');
        $this->hogwartsCards->pickCardsForLocation(6, 'deck', 'revealed');

        $playerHeroes = self::getCollectionFromDB("SELECT player_id id, player_hero heroId FROM player", true);
        foreach ($playerHeroes as $player_id => $heroId) {
            $deck = $this->heroDecks[$heroId];
            $cards = $this->hogwartsCardsLibrary->heroStartingCards($heroId);
            $deck->createCards($cards, 'deck');
            $deck->shuffle('deck');
            $deck->pickCards(5, 'deck', $player_id);
        }



        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)


        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
        $isActivePlayer = $current_player_id == self::getActivePlayerId();

        $result['game_number'] = self::getGameStateValue('game_number');

        $result['location_total'] = self::getGameStateValue('location_total');

        $result['location_number'] = self::getGameStateValue('location_number');

        $result['location_marker_total'] = $this->getLocation()['max_tokens'];

        $result['location_marker'] = self::getGameStateValue('location_marker');

        $result['players'] = self::getPlayerStats();

        $result['active_player'] = self::getActivePlayerId();

        $result['hogwarts_cards_descriptions'] = $this->hogwartsCardsLibrary->hogwartsCards;

        $result['hand'] = $this->getDeck($current_player_id)->getCardsInLocation('hand');

        $result['played_cards'] = $this->getDeck(self::getActivePlayerId())->getCardsInLocation('played');

        $result['hogwarts_cards'] = $this->hogwartsCards->getCardsInLocation('revealed');

        $result['effects'] = $this->getActiveEffects();

        if ($isActivePlayer == true) {
            $result['acquirable_hogwarts_cards'] = $this->getAcquirableHogwartsCards($current_player_id);
        }

        $result['villain_descriptions'] = $this->villainCardsLibrary->villainCards;

        $result['villains_max'] = self::getGameStateValue('villains_max');
        $result['villains_left'] = $this->villainCards->countCardInLocation('deck');
        $villain1 = $this->villainCards->getPlayerHand(1);
        if (count($villain1) > 0) {
            $result['villain_1'] = reset($villain1);
            $result['villain_1_dmg'] = self::getGameStateValue('villain_1_dmg');
        }
        $villain2 = $this->villainCards->getPlayerHand(2);
        if (count($villain2) > 0) {
            $result['villain_2'] = reset($villain2);
            $result['villain_2_dmg'] = self::getGameStateValue('villain_2_dmg');
        }
        $villain3 = $this->villainCards->getPlayerHand(3);
        if (count($villain3) > 0) {
            $result['villain_3'] = reset($villain3);
            $result['villain_3_dmg'] = self::getGameStateValue('villain_3_dmg');
        }

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getPlayerName($playerId) {
        self::loadPlayersBasicInfos()[$playerId]['player_name'];
    }

    function getPlayerStats() {
        $sql = "SELECT player_id id, player_hero hero_id, player_health health, player_influence influence, player_attack attack, player_score score FROM player ";
        $players = self::getCollectionFromDb($sql);

        foreach ($players as $player_id => $player) {
            // Add hero name
            $players[$player_id]['hero_name'] = $this->getHeroName($player['hero_id']);

            // Add cards
            $players[$player_id]['hand_card_count'] = $this->getDeck($player_id)->countCardInLocation('hand');
            $players[$player_id]['discard_cards'] = $this->getDeck($player_id)->getCardsInLocation('discard');
            $players[$player_id]['discard_cards_count'] = $this->getDeck($player_id)->countCardInLocation('discard');
        }

        return $players;
    }

    function getHeroIdsInGame() {
        $sql = "SELECT player_hero FROM player order by player_hero";
        return self::getCollectionFromDb($sql);
    }

    function getActiveEffects($trigger = null) {
        $sql = "SELECT effect_id id, effect_key, effect_trigger, effect_name name, effect_source source, effect_source_id source_id, effect_source_card_id source_card_id FROM effect";
        if ($trigger != null) {
            $sql .= " WHERE effect_resolved is false and effect_trigger = '$trigger'";
        }
        return self::getCollectionFromDb($sql);
    }

    function getEffectById($effectId) {
        $sql = "SELECT effect_id id, effect_key, effect_trigger, effect_name name, effect_source source, effect_source_id source_id, effect_source_card_id source_card_id FROM effect WHERE effect_id = $effectId";
        return self::getCollectionFromDb($sql);
    }

    function markEffectAsResolved($effectId) {
        self::DbQuery("UPDATE effect SET effect_resolved = true WHERE effect_id = $effectId");
    }

    function unmarkAllResolvedEffects() {
        self::DbQuery("UPDATE effect SET effect_resolved = false");
    }

    function addEffect($effect_key, $effect_trigger, $name, $source, $source_id, $source_card_id) {
        self::DbQuery("INSERT INTO effect(effect_key, effect_trigger, effect_name, effect_source, effect_source_id, effect_source_card_id) VALUES('$effect_key', '$effect_trigger', '$name', '$source', '$source_id', '$source_card_id')");
    }

    function removeEffects($source, $source_id) {
        self::DbQuery("DELETE FROM effect WHERE effect_source = '$source' and effect_source_id = '$source_id'");
    }

    function clearAllEffects() {
        self::DbQuery("DELETE FROM effect");
    }

    function getAcquirableHogwartsCards($playerId) {
        $influence = $this->getPlayerInfluence($playerId);
        $cardIds = array();
        $revealedCards = $this->hogwartsCards->getCardsInLocation('revealed');
        foreach ($revealedCards as $cardId => $card) {
            $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
            if ($influence >= $hogwartsCard->cost) {
                $cardIds[] = $cardId;
            }
        }
        return $cardIds;
    }

    function getHeroName($heroId) {
        switch ($heroId) {
            case HogwartsCards::$harryId:
                return clienttranslate("Harry");
            case HogwartsCards::$ronId:
                return clienttranslate("Ron");
            case HogwartsCards::$hermioneId:
                return clienttranslate("Hermione");
            case HogwartsCards::$nevilleId:
                return clienttranslate("Neville");
        }
    }

    function getHeroId($playerId) {
        return self::getUniqueValueFromDB("SELECT player_hero FROM player where player_id = " . $playerId);
    }

    function getHealth($playerId) {
        return self::getUniqueValueFromDB("SELECT player_health FROM player where player_id = ${playerId}");
    }

    function getHealthByHeroId($heroId) {
        return self::getUniqueValueFromDB("SELECT player_health FROM player where player_hero = " . $heroId);
    }

    function getAllPlayerHealth() {
        return self::getUniqueValueFromDB("SELECT player_id id, player_health health FROM player");
    }

    function getPlayerInfluence($playerId) {
        return self::getUniqueValueFromDB('select player_influence from player where player_id = ' . $playerId);
    }

    function gainInfluence($playerId, $gain) {
        self::DbQuery("UPDATE player set player_influence = player_influence + ${gain} where player_id = ${playerId}");
    }

    function getPlayerAttack($playerId) {
        return self::getUniqueValueFromDB('select player_attack from player where player_id = ' . $playerId);
    }

    function gainAttack($playerId, $gain) {
        self::DbQuery("UPDATE player set player_attack = player_attack + ${gain} where player_id = ${playerId}");
    }

    function decreaseAttack($playerId, $decrease) {
        self::DbQuery("UPDATE player set player_attack = player_attack - ${decrease} where player_id = ${playerId}");
    }

    function gainHealth($playerId, $gain) {
        self::DbQuery("UPDATE player set player_health = player_health + ${gain} where player_id = ${playerId}");
    }

    function gainHealthByHeroId($heroId, $gain) {
        self::DbQuery("UPDATE player set player_health = player_health + ${gain} where player_hero = ${heroId}");
    }

    function decreaseHealth($playerId, $decrease) {
        self::DbQuery("UPDATE player set player_health = player_health - ${decrease} where player_id = ${playerId}");
    }

    function getDeck($playerId) {
        return $this->heroDecks[$this->getHeroId($playerId)];
    }

    function getLocation() {
        return $this->locations[self::getGameStateValue('game_number')][self::getGameStateValue('location_number')];
    }

    function getHealthIcon() {
        return '<div class="icon health_icon"></div>';
    }

    function getInfluenceIcon() {
        return '<div class="icon influence_icon"></div>';
    }

    function getAttackIcon() {
        return '<div class="icon attack_icon"></div>';
    }

    function getChooseOptions($choiceAction) {
        $choices = array();
        switch ($choiceAction) {
            case 'c[+1att|+2hp]':
                $choices['option_1'] = '+1 ' . $this->getAttackIcon();
                $choices['option_2'] = '+2 ' . $this->getHealthIcon();
                break;
            case 'c[+2inf|+1inf_all]':
                $choices['option_1'] = '+2 ' . $this->getInfluenceIcon();
                $choices['option_2'] = clienttranslate('ALL Heroes') . ' +1 ' . $this->getInfluenceIcon();
                break;
            case 'c[+2hp_any]':
                foreach ($this->getHeroIdsInGame() as $heroId => $whatever) {
                    $choices["option_${heroId}"] = $this->getHeroName($heroId) . ' +2 ' . $this->getHealthIcon();
                }
                break;
            case 'c[+1att|+2hp_any]':
                $choices['option_9'] = '+1 ' . $this->getAttackIcon();
                foreach ($this->getHeroIdsInGame() as $heroId => $whatever) {
                    $choices["option_${heroId}"] = $this->getHeroName($heroId) . ' +2 ' . $this->getHealthIcon();
                }
                break;
        }
        return $choices;
    }

    function executeAction($action, $option = 0) {
        $executionComplete = true;
        switch ($action) {
            case '+1inf':
                $this->gainInfluence(self::getActivePlayerId(), 1);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${influence_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'influence_icon' => $this->getInfluenceIcon()
                    )
                );
                break;
            case '+2inf':
                $this->gainInfluence(self::getActivePlayerId(), 2);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 2 ${influence_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'influence_icon' => $this->getInfluenceIcon()
                    )
                );
                break;
            case '+1att':
                $this->gainAttack(self::getActivePlayerId(), 1);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${attack_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'attack_icon' => $this->getAttackIcon()
                    )
                );
                break;
            case '+2att':
                $this->gainAttack(self::getActivePlayerId(), 2);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 2 ${attack_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'attack_icon' => $this->getAttackIcon()
                    )
                );
                break;
            case '+1inf_+1att_xAllyPlayed':
                $playerId = self::getActivePlayerId();
                $this->gainInfluence($playerId, 1);
                $attack = 0;
                $deck = self::getDeck($playerId);
                foreach ($deck->getCardsInLocation('played') as $cardId => $card) {
                    if ($this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg'])->type == HogwartsCards::$allyType) {
                        $attack ++;
                    }
                }
                $this->gainAttack($playerId, $attack);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 {influence_icon} and ${attack} ${attack_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'attack' => $attack,
                        'attack_icon' => $this->getAttackIcon(),
                        'influence_icon' => $this->getInfluenceIcon()
                    )
                );
                break;
            case '+1hp':
                $playerId = self::getActivePlayerId();
                $healing = min(array(10 - $this->getHealth($playerId), 1));
                $this->gainHealth($playerId, $healing);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains ${healing} ${health_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'healing' => $healing,
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                break;
            case '+1hp_all':
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('ALL Heroes gain 1 ${health_icon}'),
                    array (
                        'players' => $this->getPlayerStats(),
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                break;
            case '+1card':
                $playerId = self::getActivePlayerId();
                $newHandCards = self::getDeck($playerId)->pickCards(1, 'deck', $playerId);
                self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} draws a card'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                    )
                );
                break;
            case '+1card_all':
                foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                    $newHandCards = $this->getDeck($playerId)->pickCards(1, 'deck', $playerId);
                    self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
                }
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('ALL Heroes draw a card'),
                    array (
                        'players' => $this->getPlayerStats()
                    )
                );
                break;
            case '+1att_+1card':
                $playerId = self::getActivePlayerId();
                $this->gainAttack($playerId, 1);
                $newHandCards = self::getDeck($playerId)->pickCards(1, 'deck', $playerId);
                self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${attack_icon} and draws a card'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'attack_icon' => $this->getAttackIcon()
                    )
                );
                break;
            case '+1att_+1hp':
                $playerId = self::getActivePlayerId();
                $this->gainAttack($playerId, 1);
                $healing = min(array(10 - $this->getHealth($playerId), 1));
                $this->gainHealth($playerId, $healing);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 {attack_icon} and ${healing} ${health_icon}'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'healing' => $healing,
                        'health_icon' => $this->getHealthIcon(),
                        'attack_icon' => $this->getAttackIcon()
                    )
                );
                break;
            case '+2inf_+1card':
                $playerId = self::getActivePlayerId();
                $this->gainInfluence($playerId, 2);
                $newHandCards = self::getDeck($playerId)->pickCards(1, 'deck', $playerId);
                self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 2 ${influence_icon} and draws a card'),
                    array (
                        'player_name' => self::getActivePlayerName(),
                        'players' => $this->getPlayerStats(),
                        'influence_icon' => $this->getInfluenceIcon()
                    )
                );
                break;
            case '+1att_+1hp_all':
                $this->gainAttack(self::getActivePlayerId(), 1);
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${attack_icon} and ALL Heroes gain 1 ${health_icon}'),
                    array (
                        'players' => $this->getPlayerStats(),
                        'attack_icon' => $this->getAttackIcon(),
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                break;
            case '+1att_all_+1inf_all_+1hp_all_+1card_all':
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $this->gainAttack($playerId, 1);
                    $this->gainInfluence($playerId, 1);
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                    $newHandCards = $this->getDeck($playerId)->pickCards(1, 'deck', $playerId);
                    self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
                }
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('ALL Heroes gain 1 ${attack_icon}, 1 ${influence_icon}, 1 ${health_icon} and draw a card'),
                    array (
                        'players' => $this->getPlayerStats(),
                        'attack_icon' => $this->getAttackIcon(),
                        'influence_icon' => $this->getInfluenceIcon(),
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                break;
            case 'c[+2hp_any]':
                if ($option == 0) {
                    $executionComplete = false;
                } else {
                    $healing = min(array(10 - $this->getHealthByHeroId($option), 2));
                    $this->gainHealthByHeroId($option, $healing);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains ${healing} ${health_icon}'),
                        array (
                            'player_name' => $this->getPlayerName($option),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon(),
                            'players' => $this->getPlayerStats()
                        )
                    );
                }
                break;
            case 'c[+1att|+2hp]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 1) {
                    $this->gainAttack(self::getActivePlayerId(), 1);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${attack_icon}'),
                        array (
                            'player_name' => self::getActivePlayerName(),
                            'players' => $this->getPlayerStats(),
                            'attack_icon' => $this->getAttackIcon()
                        )
                    );
                } else {
                    $playerId = self::getActivePlayerId();
                    $healing = min(array(10 - $this->getHealth($playerId), 2));
                    $this->gainHealth($playerId, $healing);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains ${healing} ${health_icon}'),
                        array (
                            'player_name' => self::getActivePlayerName(),
                            'players' => $this->getPlayerStats(),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon()
                        )
                    );
                }
                break;
            case 'c[+2inf|+1inf_all]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 1) {
                    $this->gainInfluence(self::getActivePlayerId(), 2);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 2 ${influence_icon}'),
                        array (
                            'player_name' => self::getActivePlayerName(),
                            'players' => $this->getPlayerStats(),
                            'influence_icon' => $this->getInfluenceIcon()
                        )
                    );
                } else {
                    foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                        $this->gainInfluence($playerId, 1);
                    }
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('ALL Heroes gain 1 ${influence_icon}'),
                        array (
                            'players' => $this->getPlayerStats(),
                            'influence_icon' => $this->getInfluenceIcon(),
                        )
                    );
                }
                break;
            case 'c[+1att|+2hp_any]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 9) {
                    $this->gainAttack(self::getActivePlayerId(), 1);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains 1 ${attack_icon}'),
                        array (
                            'player_name' => self::getActivePlayerName(),
                            'players' => $this->getPlayerStats(),
                            'attack_icon' => $this->getAttackIcon()
                        )
                    );
                } else {
                    $healing = min(array(10 - $this->getHealthByHeroId($option), 2));
                    $this->gainHealthByHeroId($option, $healing);
                    self::notifyAllPlayers('updatePlayerStats', clienttranslate('${player_name} gains ${healing} ${health_icon}'),
                        array (
                            'player_name' => $this->getPlayerName($option),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon(),
                            'players' => $this->getPlayerStats()
                        )
                    );
                }
                break;
            default:
                self::notifyAllPlayers('unknown_effect', 'Unknown effect: ' . $action, array ());
                break;
        }
        return $executionComplete;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    function endTurn() {
        self::checkAction("endTurn");
        $this->gamestate->nextState('endTurn');
    }

    function playCard($cardId) {
        self::checkAction("playCard");
        self::setGameStateValue('played_card_id', $cardId);
        $this->gamestate->nextState('playCard');
    }

    function playCardOption($option) {
        self::checkAction("decidePlayCardOption");
        $cardId = self::getGameStateValue('played_card_id');
        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);
        $card = $deck->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);

        $this->executeAction($hogwartsCard->onPlay, $option);

        $this->gamestate->nextState();
    }

    function decideEffectOption($option) {
        self::checkAction("decideEffectOption");

        $effects = $this->getEffectById(self::getGameStateValue('effect_id_with_option'));
        $effect = reset($effects);

        $this->executeAction($effect['effect_key'], $option);

        $this->gamestate->nextState();
    }

    function attackVillain($slot) {
        self::checkAction('attackVillain');

        $playerId = self::getActivePlayerId();
        $attack = $this->getPlayerAttack($playerId);
        if ($attack < 1) {
            throw new feException('You don\'t have any attack tokens to attack a villain');
        }

        $dmg = self::getGameStateValue("villain_${slot}_dmg") + 1;
        $this->decreaseAttack($playerId, 1);

        $cards = $this->villainCards->getPlayerHand($slot);
        $card = reset($cards);
        $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);

        if ($dmg < $villainCard->health) {
            self::setGameStateValue("villain_${slot}_dmg", $dmg);
            self::notifyAllPlayers(
                'villainAttacked',
                clienttranslate('${player_name} attacked ${villain_name} for 1 ${attack_token}'),
                array (
                    'players' => $this->getPlayerStats(),
                    'player_name' => self::getActivePlayerName(),
                    'villain_name' => $villainCard->name,
                    'attack_token' => $this->getAttackIcon()
                )
            );
            $this->gamestate->nextState('villainAttacked');
        } else {

            $this->removeEffects(self::$SOURCE_VILLAIN, $villainCard->id);

            self::setGameStateValue("villain_${slot}_dmg", 0);
            $this->villainCards->moveCard($card['id'], 'discard');
            self::notifyAllPlayers(
                'villainDefeated',
                clienttranslate('${player_name} defeated ${villain_name}'),
                array (
                    'players' => $this->getPlayerStats(),
                    'player_name' => self::getActivePlayerName(),
                    'villain_name' => $villainCard->name,
                    'villain_id' => $villainCard->id,
                    'villain_slot' => $slot,
                    'effects' => $this->getActiveEffects()
                )
            );

            $executionComplete = $this->executeAction($villainCard->reward);
            if ($executionComplete) {
                $this->gamestate->nextState('villainDefeated');
            } else {
                // TODO state to decide on rewards (not relevant for game1)
            }
        }
    }

    function acquireHogwartsCard($cardId) {
        self::checkAction("acquireHogwartsCard");
        $playerId = self::getActivePlayerId();
        $card = $this->hogwartsCards->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);

        // Check the costs and pay the price
        if ($this->getPlayerInfluence($playerId) < $hogwartsCard->cost) {
            throw new feException('You don\'t have enough influence to acquire that hogwarts card');
        }
        self::DbQuery("UPDATE player set player_influence = player_influence - " . $hogwartsCard->cost . " where player_id = " . $playerId);

        // Add acquired card to discard pile
        $this->hogwartsCards->moveCard($cardId, 'dev0');
        // TODO check effects on acquire_hogwarts_card
        $deck = self::getDeck($playerId);
        $deck->createCards(array($this->hogwartsCardsLibrary->asCard($hogwartsCard)), 'new', $playerId);
        $newCardId = key($deck->getCardsInLocation('new'));
        $deck->moveCard($newCardId, 'discard');

        self::notifyAllPlayers(
            'acquireHogwartsCard',
            clienttranslate('${player_name} acquires ${card_name} for ${card_cost} ${influence_token}'),
            array (
                'players' => $this->getPlayerStats(),
                'acquired_card' => $deck->getCard($newCardId),
                'acquirable_hogwarts_cards' => $this->getAcquirableHogwartsCards($playerId),
                'card_id' => $cardId,
                'new_card_id' => $newCardId,
                'player_id' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $hogwartsCard->name,
                'card_cost' => $hogwartsCard->cost,
                'influence_token' => $this->getInfluenceIcon()
            )
        );
        $this->gamestate->nextState('acquireHogwartsCard');
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    function argInitTurnEffects() {
        return $this->getActiveEffects();
    }

    function argChooseCardOption() {
        $cardId = self::getGameStateValue('played_card_id');
        $card = self::getDeck(self::getActivePlayerId())->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
        return $this->getChooseOptions($hogwartsCard->onPlay);
    }

    function argChooseEffectOption() {
        $effects = $this->getEffectById(self::getGameStateValue('effect_id_with_option'));
        $effect = reset($effects);
        return $this->getChooseOptions($effect['effect_key']);
    }
    
    function argVillainAttacked() {
        return array(
            1 => self::getGameStateValue('villain_1_dmg'),
            2 => self::getGameStateValue('villain_2_dmg'),
            3 => self::getGameStateValue('villain_3_dmg')
        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stInitTurn() {
        // reset dark_arts_cards and active villain
        self::setGameStateInitialValue('dark_arts_cards_revealed', 0);
        self::setGameStateInitialValue('villain_turn_slot', 0);

        // check hand cards for onHand effects
        $playerHeroes = self::getCollectionFromDB("SELECT player_id id, player_hero heroId FROM player", true);
        foreach ($playerHeroes as $player_id => $heroId) {
            $handCards = $this->heroDecks[$heroId]->getCardsInLocation('hand');
            foreach ($handCards as $cardId => $card) {
                $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
                if ($hogwartsCard->onHand != null) {
                    switch ($hogwartsCard->onHand) {
                        case 'max1dmg':
                            $this->addEffect('max1dmg', self::$TRIGGER_ON_DMG_DARK_ARTS_OR_VILLAIN, $this->getHeroName($heroId), self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                            break;
                    }
                }
            }
        }

        $villainsMax = self::getGameStateValue('villains_max');
        for($slot = 1; $slot <= $villainsMax; $slot ++) {
            $cards = $this->villainCards->getPlayerHand($slot);
            $card = reset($cards);
            $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);
            if ($villainCard->effect != null) {
                switch ($villainCard->effect) {
                    case '2dmg_onLocationToken':
                        $this->addEffect('2dmg', self::$TRIGGER_ON_LOCATION_TOKEN, $villainCard->name, self::$SOURCE_VILLAIN, $villainCard->id, $villainCard->id);
                        break;
                    case '1dmg_onDiscard':
                        $this->addEffect('1dmg', self::$TRIGGER_ON_DISCARD, $villainCard->name, self::$SOURCE_VILLAIN, $villainCard->id, $villainCard->id);
                        break;
                    default:
                        self::notifyAllPlayers('unknown_effect', 'Unknown villain effect: ' . $villainCard->effect, array ());
                }
            }
        }

        $this->gamestate->nextState();
    }

    function stInitTurnEffects() {
        // nothing to do here. Meaning of this step is to get the args method called to have the current effects visible in the ui
        $this->gamestate->nextState();
    }

    function stVillainAbilities() {
        $activeVillainSlot = self::getGameStateValue('villain_turn_slot');
        $activeVillainSlot ++;
        $villainsMax = self::getGameStateValue('villains_max');
        for($slot = $activeVillainSlot; $slot <= $villainsMax; $slot ++) {
            $cards = $this->villainCards->getPlayerHand($slot);
            $card = reset($cards);
            $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);
            if ($villainCard->ability != null) {
                self::setGameStateValue('villain_turn_slot', $activeVillainSlot);
                self::setGameStateValue('villain_turn_id', $villainCard->id);
                $this->gamestate->nextState('villainTurn');
                return;
            }
        }
        $this->gamestate->nextState('playerTurn');
    }

    function stVillainTurn() {
        $activeVillainId = self::getGameStateValue('villain_turn_id');
        $villainCard = $this->villainCardsLibrary->villainCards[$activeVillainId];
        switch ($villainCard->ability) {
            case '1dmg':
                $this->decreaseHealth(self::getActivePlayerId(), 1);
                self::notifyAllPlayers(
                    'villainTurn',
                    clienttranslate('${player_name} loses 1 ${health_icon} due to ${villain_name}\'s ability'),
                    array (
                        'players' => self::getPlayerStats(),
                        'villain_name' => $villainCard->name,
                        'player_name' => self::getActivePlayerName(),
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                break;
            default:
                self::notifyAllPlayers('unknown_ability', 'Unknown villain ability: ' . $villainCard->effect, array ());
        }

        // TODO check if a player is stunned

        $this->gamestate->nextState('executed');
    }

    function stBeforePlayerTurn() {
        // just a step for UI to prep for turn
        $this->gamestate->nextState();
    }

    function stPlayCard() {
        $cardId = self::getGameStateValue('played_card_id');
        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);
        $card = $deck->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
        if (is_null($card) || $card['location'] != 'hand') {
            throw new feException( "Selected card is not in your hand" );
        }
        $deck->moveCard($cardId, 'played');

        self::notifyAllPlayers('cardPlayed', clienttranslate('${player_name} plays ${card_name}'),
            array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => $playerId,
                'card_name' => $hogwartsCard->name,
                'card_id' => $cardId,
                'card_played' => $card,
            )
        );

        $executionComplete = $this->executeAction($hogwartsCard->onPlay);

        if ($executionComplete == true) {
            $this->gamestate->nextState('cardResolved');
        } else {
            $this->gamestate->nextState('chooseCardOption');
        }
    }

    function stPlayCardResolved() {
        $cardId = self::getGameStateValue('played_card_id');
        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);
        $card = $deck->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);

        $effectsModified = false;
        if ($hogwartsCard->onHand != null) {
            // remove onHand effects
            $this->removeEffects(self::$SOURCE_HOGWARTS_CARD, $cardId);
            $effectsModified = true;
        }

        if ($hogwartsCard->onPlayEffect != null) {
            switch ($hogwartsCard->onPlayEffect) {
                case 'c[+2hp_any_onDefVil]':
                    $this->addEffect('c[+2hp_any]', self::$TRIGGER_ON_DEFEAT_VILLAIN, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    break;
                case '+1inf_onDefVil':
                    $this->addEffect('+1inf', self::$TRIGGER_ON_DEFEAT_VILLAIN, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    break;
                case 'spells_top_deck':
                    $this->addEffect('spells_top_deck', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    break;
                case 'items_top_deck':
                    $this->addEffect('items_top_deck', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    break;
                case 'allies_on_top':
                    $this->addEffect('allies_on_top', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    break;
            }
            $effectsModified = true;
        }
        if ($effectsModified) {
            self::notifyAllPlayers('effects', '', array('effects' => $this->getActiveEffects()));
        }

        // reset game states
        self::setGameStateInitialValue('played_card_id', 0);
        self::setGameStateInitialValue('play_card_option', 0);

        $this->gamestate->nextState();
    }
    
    function stVillainAttacked() {
        // Just a step to update UI
        $this->gamestate->nextState();
    }

    function stVillainDefeated() {
        $villainsLeft = $this->villainCards->countCardInLocation('deck');
        $activeVillains = $this->villainCards->countCardInLocation('hand');

        if ($villainsLeft == 0 && $activeVillains == 0) {
            self::DbQuery("UPDATE player set player_score = 1");
            self::notifyAllPlayers('victory', clienttranslate('All Villains defeated. Congratulations'), array ());
            $this->gamestate->nextState('victory');
        } else {
            $this->gamestate->nextState('villainDefeatedEffects');
        }
    }

    function stVillainDefeatedEffects() {
        $effects = $this->getActiveEffects(self::$TRIGGER_ON_DEFEAT_VILLAIN);
        foreach ($effects as $effectId => $effect) {

            $executionComplete = $this->executeAction($effect['effect_key']);
            if ($executionComplete) {
                self::setGameStateValue('effect_id_with_option', $effectId);
                $this->gamestate->nextState('chooseEffectOption');
                return;
            }

            $this->markEffectAsResolved($effectId);
        }
        $this->unmarkAllResolvedEffects();
        $this->gamestate->nextState('effectsResolved');
    }

    function stEndTurn() {
        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);

        // Clean up board and draw 5 new cards
        $deck->moveAllCardsInLocation('hand', 'discard');
        $deck->moveAllCardsInLocation('played', 'discard');
        self::DbQuery("UPDATE player set player_attack = 0, player_influence = 0 where player_id = " . $playerId);

        // Refill hogwarts cards
        $missingCards = 6 - $this->hogwartsCards->countCardInLocation('revealed');
        $newHogwartsCards = $this->hogwartsCards->pickCardsForLocation($missingCards, 'deck', 'revealed');

        // Notify players
        self::notifyAllPlayers(
            'endTurn',
            clienttranslate('${player_name} ends the turn'),
            array (
                'players' => self::getPlayerStats(),
                'new_hogwarts_cards' => $newHogwartsCards,
                'player_id' => $playerId,
                'player_name' => self::getActivePlayerName(),
            )
        );
        if ($this->villainCards->countCardInLocation('deck') > 0 && $this->villainCards->countCardInLocation('hand') < self::getGameStateValue('villains_max')) {
            $this->gamestate->nextState('revealVillain');
        } else {
            $this->gamestate->nextState('refillHandCards');
        }
    }

    function stRevealVillain() {
        $villainsMax = self::getGameStateValue('villains_max');
        for($slot = 1; $slot <= $villainsMax; $slot ++) {
            if ($this->villainCards->countCardInLocation('hand', $slot) == 0) {
                $this->villainCards->pickCard('deck', 1);
                // TODO add effects of revealed villain then run onVillainRevealed actions (Deatheater)
                $cards = $this->villainCards->getPlayerHand($slot);
                $card = reset($cards);
                $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);
                self::notifyAllPlayers(
                    'villainRevealed',
                    clienttranslate('${villain_name} is revealed'),
                    array (
                        'villain_name' => $villainCard->name,
                        'villain_slot' => $slot,
                        'villain' => $card
                    )
                );
            }
        }
        $this->gamestate->nextState();
    }

    function stRefillHandCards() {
        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);

        $newHandCards = $deck->pickCards(5, 'deck', $playerId);

        self::notifyPlayer($playerId, 'refillHandCards', '', array('new_hand_cards' => $newHandCards));
        self::notifyAllPlayers(
            'refillHandCardsLog',
            clienttranslate('${player_name} draws 5 new cards'),
            array ('player_name' => self::getActivePlayerName(), 'players' => self::getPlayerStats())
        );
        $this->gamestate->nextState();
    }

    function stCleanEffectsNextPlayer() {
        $this->clearAllEffects();

        // Next Player
        $this->activeNextPlayer();
        self::giveExtraTime(self::getCurrentPlayerId());
        $this->gamestate->nextState();
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
