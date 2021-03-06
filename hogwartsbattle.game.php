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
    private static $TRIGGER_ON_DISCARD = 'onDiscard';
    private static $TRIGGER_ON_ACQUIRE = 'onAcquire';
    private static $TRIGGER_ON_DEFEAT_VILLAIN = 'onDefeatVillain';
    private static $TRIGGER_ON_DMG_DARK_ARTS_OR_VILLAIN = 'onDmgDarkArtsOrVillain';
    private static $TRIGGER_ON_LOCATION_TOKEN = 'onLocationToken';
    private static $TRIGGER_ON_DRAW_CARD = 'onDrawCard';

    private static $SOURCE_HOGWARTS_CARD = 'hogwartsCard';
    private static $SOURCE_VILLAIN = 'villain';
    private static $SOURCE_DARK_ARTS_CARD = 'darkArtsCard';

    private static $STATE_DARK_ARTS = 1;
    private static $STATE_VILLAINS = 2;

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
            'autoplay' => 15,

            'location_total' => 21,
            'location_number' => 22,
            'location_marker' => 23,

            'dark_arts_cards_revealed' => 25,

            'source_is_dark' => 27, // villain or dark arts card => 1 else 0
            'discard_return_state' => 28,

            'villains_max' => 30,
            'villain_1_dmg' => 31,
            'villain_2_dmg' => 32,
            'villain_3_dmg' => 33,
            'villain_turn_slot' => 40,
            'villain_turn_id' => 41,

            // game options
            'game_number' => 101,
            'location_first_marker' => 103,
            'location_reveal_marker' => 104,
            'hero_selection' => 105,
            'allow_hogwarts_cards_replace' => 108
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
        $this->darkArtsCards->autoreshuffle = true;
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

        $gameNr = self::getGameStateValue('game_number');
        $villains_max = min(array($gameNr, 3));

        // Init global values with their initial values
        self::setGameStateInitialValue('played_card_id', 0);
        self::setGameStateInitialValue('play_card_option', 0);
        self::setGameStateInitialValue('effect_id_with_option', 0);
        self::setGameStateInitialValue('autoplay', 0);

        self::setGameStateInitialValue('location_total', count($this->locations[$gameNr]));
        self::setGameStateInitialValue('location_number', 1);
        self::setGameStateInitialValue('location_marker', self::getGameStateValue('location_first_marker'));

        self::setGameStateInitialValue('dark_arts_cards_revealed', 0);
        self::setGameStateInitialValue('source_is_dark', 0);
        self::setGameStateInitialValue('discard_return_state', 0);

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
        self::initStat('table', 'villains_defeated', 0);
        self::initStat('table', 'locations_lost', 0);
        self::initStat('table', 'locations_token_added', 0);
        self::initStat('table', 'locations_token_removed', 0);
        self::initStat('table', 'locations_full_prevention', 0);

        self::initStat('player', 'hero', 0);
        self::initStat('player', 'turns_number', 0);
        self::initStat('player', 'gained_influence', 0);
        self::initStat('player', 'influence_spent_on_acquire', 0);
        self::initStat('player', 'cards_acquired', 0);
        self::initStat('player', 'items_acquired', 0);
        self::initStat('player', 'spells_acquired', 0);
        self::initStat('player', 'allies_acquired', 0);
        self::initStat('player', 'gained_attack', 0);
        self::initStat('player', 'villains_damaged', 0);
        self::initStat('player', 'villains_defeated', 0);
        self::initStat('player', 'healed_self', 0);
        self::initStat('player', 'healed_others', 0);
        self::initStat('player', 'healed_by_others', 0);
        self::initStat('player', 'health_lost', 0);
        self::initStat('player', 'stunned', 0);
        self::initStat('player', 'dark_arts_drawn', 0);
        self::initStat('player', 'cards_discarded', 0);
        self::initStat('player', 'cards_drawn', 0);
        self::initStat('player', 'locations_token_removed', 0);

        foreach ($playerHeroes as $playerId => $heroId) {
            self::setStat($heroId, 'hero', $playerId);
        }

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
        $result['villain_descriptions'] = $this->villainCardsLibrary->villainCards;
        $result['dark_arts_descriptions'] = $this->darkArtsCardsLibrary->darkArtsCards;

        $result['hand'] = $this->getDeck($current_player_id)->getCardsInLocation('hand');

        $result['played_cards'] = $this->getDeck(self::getActivePlayerId())->getCardsInLocation('played');

        $result['dark_arts_cards'] = $this->darkArtsCards->getCardsInLocation('hand');

        $result['hogwarts_cards'] = $this->hogwartsCards->getCardsInLocation('revealed');

        $result['effects'] = $this->getActiveEffects();

        if ($isActivePlayer == true) {
            $result['acquirable_hogwarts_cards'] = $this->getAcquirableHogwartsCards($current_player_id);
        }

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

    function getPlayerIdByHeroId($heroId) {
        return self::getUniqueValueFromDB("SELECT player_id FROM player where player_hero = ${heroId}");
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
        $sql = "SELECT effect_id id, effect_key, effect_trigger, effect_name name, effect_source source, effect_source_id source_id, effect_source_card_id source_card_id, effect_player_id player_id FROM effect";
        if ($trigger != null) {
            $sql .= " WHERE effect_resolved is false and effect_trigger = '$trigger'";
        }
        return self::getCollectionFromDb($sql);
    }

    function getEffectById($effectId) {
        $sql = "SELECT effect_id id, effect_key, effect_trigger, effect_name name, effect_source source, effect_source_id source_id, effect_source_card_id source_card_id, effect_player_id player_id FROM effect WHERE effect_id = $effectId";
        return self::getCollectionFromDb($sql);
    }

    function markEffectAsResolved($effectId) {
        self::DbQuery("UPDATE effect SET effect_resolved = true WHERE effect_id = $effectId");
    }

    function unmarkAllResolvedEffects() {
        self::DbQuery("UPDATE effect SET effect_resolved = false");
    }

    function addEffect($effect_key, $effect_trigger, $name, $source, $source_id, $source_card_id, $player_id = null) {
        $sql = "INSERT INTO effect(effect_key, effect_trigger, effect_name, effect_source, effect_source_id, effect_source_card_id, effect_player_id) VALUES ".
            "('$effect_key', '$effect_trigger', '$name', '$source', '$source_id', '$source_card_id', ";
        if ($player_id == null) {
            $sql .= "null";
        } else {
            $sql .= "'$player_id'";
        }
        $sql .= ")";
        self::DbQuery($sql);
    }

    function removeEffects($source, $source_id, $playerId) {
        $sql = "DELETE FROM effect WHERE effect_source = '$source' and effect_source_id = '$source_id'";
        if ($playerId != null) {
            $sql .= " and effect_player_id = '$playerId'";
        } else {
            $sql .= " and effect_player_id is null";
        }
        self::DbQuery($sql);
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

    function getActivePlayerColor($playerId) {
        foreach (self::loadPlayersBasicInfos() as $id => $info) {
            if ($playerId == $id) {
                return $info['player_color'];
            }
        }
    }

    function getActiveHeroName($playerId = null) {
        if ($playerId == null) {
            $playerId = self::getActivePlayerId();
        }
        return '<span class="playername" style="color: #' . $this->getActivePlayerColor($playerId) . ';">' .
            $this->getHeroName($this->getHeroId($playerId)) . '</span>';

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

    function getNewlyStunnedPlayers() {
        return self::getCollectionFromDb("SELECT player_id id, player_hero hero_id FROM player WHERE player_health <= 0 and player_stunned is false");
    }

    function setDiscards($playerId, $discards) {
        self::DbQuery("UPDATE player set player_discards = ${discards} where player_id = ${playerId}");
    }

    function playerDiscardedACard($playerId) {
        self::DbQuery("UPDATE player set player_discards = player_discards - 1 where player_id = ${playerId}");
    }

    function getDiscards($playerId) {
        return self::getUniqueValueFromDB("SELECT player_discards FROM player where player_id = ${playerId}");
    }

    function getPlayersNeedToDiscard() {
        return self::getCollectionFromDb("SELECT player_id id, player_hero hero_id, player_discards discards FROM player WHERE player_discards > 0");
    }

    function getHealthByHeroId($heroId) {
        $player = self::getObjectFromDB("SELECT player_health health, player_stunned stunned FROM player where player_hero = ${heroId}");
        if ($player['stunned']) {
            // treat stunned as full health to prevent healing
            return 10;
        } else {
            return $player['health'];
        }
    }

    function getAllPlayerHealth() {
        return self::getCollectionFromDb("SELECT player_id id, player_health health, player_stunned stunned FROM player");
    }

    function setStunned($playerId) {
        self::incStat(1, 'stunned', $playerId);
        self::DbQuery("UPDATE player set player_health = 0, player_attack = 0, player_influence = 0, player_stunned = true where player_id = ${playerId}");
    }

    function recoverStunnedHeroes() {
        self::DbQuery("UPDATE player set player_stunned = false, player_health = 10 where player_stunned is true");
    }

    function getPlayerInfluence($playerId) {
        return self::getUniqueValueFromDB('select player_influence from player where player_id = ' . $playerId);
    }

    function gainInfluence($playerId, $gain) {
        self::incStat($gain, 'gained_influence', $playerId);
        self::DbQuery("UPDATE player set player_influence = player_influence + ${gain} where player_id = ${playerId}");
    }

    function decreaseInfluence($playerId, $decrease) {
        self::DbQuery("UPDATE player set player_influence = player_influence - ${decrease} where player_id = ${playerId}");
    }

    function getPlayerAttack($playerId) {
        return self::getUniqueValueFromDB('select player_attack from player where player_id = ' . $playerId);
    }

    function gainAttack($playerId, $gain) {
        self::incStat($gain, 'gained_attack', $playerId);
        self::DbQuery("UPDATE player set player_attack = player_attack + ${gain} where player_id = ${playerId}");
    }

    function decreaseAttack($playerId, $decrease) {
        self::DbQuery("UPDATE player set player_attack = player_attack - ${decrease} where player_id = ${playerId}");
    }

    function gainHealth($playerId, $gain) {
        $activePlayerId = self::getActivePlayerId();
        if ($playerId == $activePlayerId) {
            self::incStat($gain, 'healed_self', $playerId);
        } else {
            self::incStat($gain, 'healed_others', $activePlayerId);
            self::incStat($gain, 'healed_by_others', $playerId);
        }
        self::DbQuery("UPDATE player set player_health = player_health + ${gain} where player_id = ${playerId}");
    }

    function gainHealthByHeroId($heroId, $gain) {
        $playerId = $this->getPlayerIdByHeroId($heroId);
        $this->gainHealth($playerId, $gain);
    }

    function decreaseHealth($playerId, $decrease) {
        if ($decrease > 1 && $this->playerMax1Damage($playerId)) {
            $decrease = 1;
        }
        self::incStat($decrease, 'health_lost', $playerId);
        self::DbQuery("UPDATE player set player_health = player_health - ${decrease} where player_id = ${playerId}");
    }

    function getDeck($playerId) {
        return $this->heroDecks[$this->getHeroId($playerId)];
    }

    function getLocation() {
        return $this->locations[self::getGameStateValue('game_number')][self::getGameStateValue('location_number')];
    }

    function isLocationFull() {
        return self::getGameStateValue('location_marker') >= $this->getLocation()['max_tokens'];
    }

    function isLocationEmpty() {
        return self::getGameStateValue('location_marker') == 0;
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

    function getLocationIcon() {
        return '<div class="icon location_icon"></div>';
    }

    function playerMax1Damage($playerId): bool {
        if (self::getGameStateValue('source_is_dark') == 1) {
            $effects = $this->getActiveEffects(self::$TRIGGER_ON_DMG_DARK_ARTS_OR_VILLAIN);
            foreach ($effects as $effectId => $effect) {
                if ($effect['effect_key'] == 'max1dmg' && $effect['player_id'] == $playerId) {
                    self::notifyAllPlayers('log', clienttranslate('${effect_name} reduces damage to 1'),
                        array ('i18n' => array('effect_name'), 'effect_name' => $effect['name'])
                    );
                    return true;
                }
            }
        }
        return false;
    }

    function drawCard($playerIds, $numberOfCards) {
        // check if there is an effect in place that prevents card drawing
        $effects = $this->getActiveEffects(self::$TRIGGER_ON_DRAW_CARD);
        foreach ($effects as $effectId => $effect) {
            if ($effect['effect_key'] == 'no_draw_cards') {
                self::notifyAllPlayers('log', clienttranslate('${effect_name} prevents card drawing'),
                    array ('i18n' => array('effect_name'), 'effect_name' => $effect['name'])
                );
                foreach ($playerIds as $playerId) {
                    self::notifyPlayer($playerId, 'important', '', array('message' => "${effect['name']} ". clienttranslate('prevents card drawing')));
                }
                return;
            }
        }
        foreach ($playerIds as $playerId) {
            self::incStat($numberOfCards, 'cards_drawn', $playerId);
            $newHandCards = self::getDeck($playerId)->pickCards($numberOfCards, 'deck', $playerId);
            self::notifyPlayer($playerId, 'newHandCards', '', array('new_hand_cards' => $newHandCards));
        }
    }

    function checkStunnedHeroes(): bool {
        $stunnedPlayers = $this->getNewlyStunnedPlayers();
        if (count($stunnedPlayers) > 0) {
            foreach ($stunnedPlayers as $playerId => $info) {
                self::notifyAllPlayers('log', '${hero_name} is stunned', array('hero_name' => $this->getActiveHeroName($playerId)));
                $this->setStunned($playerId);
                $this->executeDarkAction(null, '+1loc_token');
                $handCards = $this->getDeck($playerId)->countCardInLocation('hand');
                $this->setDiscards($playerId, intdiv($handCards, 2));
            }
            return true;
        }
        return false;
    }

    function getChooseOptions($choiceAction): array {
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
            case 'c[+2inf|+1card]':
                $choices['option_1'] = '+2 ' . $this->getInfluenceIcon();
                $choices['option_2'] = clienttranslate('Draw a card');
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

    function executeAction($action, $option = 0): bool {
        $executionComplete = true;
        $activePlayerId = self::getActivePlayerId();
        switch ($action) {
            case '+1inf':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${influence_icon}'),
                    array ('hero_name' => self::getActiveHeroName($activePlayerId), 'influence_icon' => $this->getInfluenceIcon())
                );
                $this->gainInfluence($activePlayerId, 1);
                break;
            case '+2inf':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 2 ${influence_icon}'),
                    array ('hero_name' => self::getActiveHeroName($activePlayerId), 'influence_icon' => $this->getInfluenceIcon())
                );
                $this->gainInfluence($activePlayerId, 2);
                break;
            case '+1att':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon}'),
                    array ('hero_name' => self::getActiveHeroName($activePlayerId), 'attack_icon' => $this->getAttackIcon())
                );
                $this->gainAttack($activePlayerId, 1);
                break;
            case '+2att':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 2 ${attack_icon}'),
                    array ('hero_name' => self::getActiveHeroName($activePlayerId), 'attack_icon' => $this->getAttackIcon())
                );
                $this->gainAttack($activePlayerId, 2);
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
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${influence_icon} and ${attack} ${attack_icon}'),
                    array (
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'attack' => $attack,
                        'attack_icon' => $this->getAttackIcon(),
                        'influence_icon' => $this->getInfluenceIcon()
                    )
                );
                $this->gainAttack($playerId, $attack);
                break;
            case '+1inf_+1hp_all':
                self::notifyAllPlayers('log', clienttranslate('ALL Heroes gain 1 ${influence_icon} and 1 ${health_icon}'),
                    array ('influence_icon' => $this->getInfluenceIcon(), 'health_icon' => $this->getHealthIcon())
                );
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $this->gainInfluence($playerId, 1);
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                break;
            case '+1hp':
                $healing = min(array(10 - $this->getHealth($activePlayerId), 1));
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains ${healing} ${health_icon}'),
                    array (
                        'hero_name' => self::getActiveHeroName(),
                        'healing' => $healing,
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                $this->gainHealth($activePlayerId, $healing);
                break;
            case '+1hp_all':
                self::notifyAllPlayers('log', clienttranslate('ALL Heroes gain 1 ${health_icon}'),
                    array ('health_icon' => $this->getHealthIcon())
                );
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                break;
            case '+1card':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} draws a card'),
                    array('hero_name' => self::getActiveHeroName($activePlayerId))
                );
                $this->drawCard(array($activePlayerId), 1);
                break;
            case '+1card_all':
                self::notifyAllPlayers('log', clienttranslate('ALL Heroes draw a card'), array());
                $playerIds = array();
                foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                    $playerIds[] = $playerId;
                }
                $this->drawCard($playerIds, 1);
                break;
            case '+1att_+1card':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon} and draws a card'),
                    array('hero_name' => self::getActiveHeroName($activePlayerId), 'attack_icon' => $this->getAttackIcon())
                );
                $this->gainAttack($activePlayerId, 1);
                $this->drawCard(array($activePlayerId), 1);
                break;
            case '+1att_+1hp':
                $healing = min(array(10 - $this->getHealth($activePlayerId), 1));
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon} and ${healing} ${health_icon}'),
                    array (
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'healing' => $healing,
                        'health_icon' => $this->getHealthIcon(),
                        'attack_icon' => $this->getAttackIcon()
                    )
                );
                $this->gainAttack($activePlayerId, 1);
                $this->gainHealth($activePlayerId, $healing);
                break;
            case '+2inf_+1card':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 2 ${influence_icon} and draws a card'),
                    array('hero_name' => self::getActiveHeroName($activePlayerId), 'influence_icon' => $this->getInfluenceIcon())
                );
                $this->gainInfluence($activePlayerId, 2);
                $this->drawCard(array($activePlayerId), 1);
                break;
            case '+1att_+1hp_all':
                self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon} and ALL Heroes gain 1 ${health_icon}'),
                    array(
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'attack_icon' => $this->getAttackIcon(),
                        'health_icon' => $this->getHealthIcon())
                );
                $this->gainAttack($activePlayerId, 1);
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                break;
            case '+1att_all_+1inf_all_+1hp_all_+1card_all':
                self::notifyAllPlayers('log', clienttranslate('ALL Heroes gain 1 ${attack_icon}, 1 ${influence_icon}, 1 ${health_icon} and draw a card'),
                    array (
                        'attack_icon' => $this->getAttackIcon(),
                        'influence_icon' => $this->getInfluenceIcon(),
                        'health_icon' => $this->getHealthIcon()
                    )
                );
                $playerIds = array();
                foreach ($this->getAllPlayerHealth() as $playerId => $player) {
                    $playerIds[] = $playerId;
                    $this->gainAttack($playerId, 1);
                    $this->gainInfluence($playerId, 1);
                    $healing = min(array(10 - $player['health'], 1));
                    $this->gainHealth($playerId, $healing);
                }
                $this->drawCard($playerIds, 1);
                break;
            case '-1loc_token':
                if (!$this->isLocationEmpty()) {
                    self::incStat(1, 'locations_token_removed');
                    self::incStat(1, 'locations_token_removed', self::getActivePlayerId());
                    $marker = self::incGameStateValue('location_marker', -1);
                    self::notifyAllPlayers('locationUpdate', clienttranslate('1 ${location_icon} was removed from the Location'),
                        array('marker' => $marker, 'location_icon' => $this->getLocationIcon())
                    );
                }
                break;
            case 'c[+2hp_any]':
                if ($option == 0) {
                    $executionComplete = false;
                } else {
                    $healing = min(array(10 - $this->getHealthByHeroId($option), 2));
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains ${healing} ${health_icon}'),
                        array (
                            'hero_name' => $this->getActiveHeroName($this->getPlayerIdByHeroId($option)),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon()
                        )
                    );
                    $this->gainHealthByHeroId($option, $healing);
                }
                break;
            case 'c[+1att|+2hp]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 1) {
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon}'),
                        array('hero_name' => self::getActiveHeroName($activePlayerId), 'attack_icon' => $this->getAttackIcon())
                    );
                    $this->gainAttack($activePlayerId, 1);
                } else {
                    $healing = min(array(10 - $this->getHealth($activePlayerId), 2));
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains ${healing} ${health_icon}'),
                        array (
                            'hero_name' => self::getActiveHeroName($activePlayerId),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon()
                        )
                    );
                    $this->gainHealth($activePlayerId, $healing);
                }
                break;
            case 'c[+2inf|+1inf_all]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 1) {
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 2 ${influence_icon}'),
                        array('hero_name' => self::getActiveHeroName($activePlayerId), 'influence_icon' => $this->getInfluenceIcon())
                    );
                    $this->gainInfluence($activePlayerId, 2);
                } else {
                    self::notifyAllPlayers('log', clienttranslate('ALL Heroes gain 1 ${influence_icon}'),
                        array('influence_icon' => $this->getInfluenceIcon())
                    );
                    foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                        $this->gainInfluence($playerId, 1);
                    }
                }
                break;
            case 'c[+2inf|+1card]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 1) {
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 2 ${influence_icon}'),
                        array('hero_name' => self::getActiveHeroName($activePlayerId), 'influence_icon' => $this->getInfluenceIcon())
                    );
                    $this->gainInfluence($activePlayerId, 2);
                } else {
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} draws a card'),
                        array('hero_name' => self::getActiveHeroName($activePlayerId))
                    );
                    $this->drawCard(array($activePlayerId), 1);
                }
                break;
            case 'c[+1att|+2hp_any]':
                if ($option == 0) {
                    $executionComplete = false;
                } else if ($option == 9) {
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains 1 ${attack_icon}'),
                        array('hero_name' => self::getActiveHeroName($activePlayerId), 'attack_icon' => $this->getAttackIcon())
                    );
                    $this->gainAttack($activePlayerId, 1);
                } else {
                    $healing = min(array(10 - $this->getHealthByHeroId($option), 2));
                    self::notifyAllPlayers('log', clienttranslate('${hero_name} gains ${healing} ${health_icon}'),
                        array (
                            'hero_name' => $this->getActiveHeroName($this->getPlayerIdByHeroId($option)),
                            'healing' => $healing,
                            'health_icon' => $this->getHealthIcon()
                        )
                    );
                    $this->gainHealthByHeroId($option, $healing);
                }
                break;
            default:
                self::notifyAllPlayers('unknown_effect', 'Unknown effect: ' . $action, array ());
                break;
        }
        if ($executionComplete) {
            self::notifyAllPlayers('updatePlayerStats', '', array('players' => $this->getPlayerStats()));
            self::notifyPlayer($activePlayerId, 'acquirableHogwartsCards', '',
                array('acquirable_hogwarts_cards' => $this->getAcquirableHogwartsCards($activePlayerId))
            );
        }
        return $executionComplete;
    }

    function executeDarkAction($sourceName, $action, $option = 0): bool {
        $executionComplete = true;
        $activePlayerId = self::getActivePlayerId();
        switch ($action) {
            case '1dmg':
                $this->decreaseHealth($activePlayerId, 1);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${hero_name} loses 1 ${health_icon}') . '${source_name}',
                    array (
                        'players' => self::getPlayerStats(),
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'health_icon' => $this->getHealthIcon(),
                        'source_name' => $sourceName != null ? " ($sourceName)" : ''
                    )
                );
                break;
            case '2dmg':
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${hero_name} loses 2 ${health_icon}') . '${source_name}',
                    array (
                        'players' => self::getPlayerStats(),
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'health_icon' => $this->getHealthIcon(),
                        'source_name' => $sourceName != null ? " ($sourceName)" : ''
                    )
                );
                $this->decreaseHealth($activePlayerId, 2);
                self::notifyAllPlayers('updatePlayerStats', '', array('players' => $this->getPlayerStats()));
                break;
            case '1dmg_all':
                foreach (self::loadPlayersBasicInfos() as $playerId => $player) {
                    $this->decreaseHealth($playerId, 1);
                }
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('ALL Heroes lose 1 ${health_icon}') . '${source_name}',
                    array (
                        'players' => self::getPlayerStats(),
                        'health_icon' => $this->getHealthIcon(),
                        'source_name' => $sourceName != null ? " ($sourceName)" : ''
                    )
                );
                break;
            case '+1loc_token':
                if ($this->isLocationFull()) {
                    self::incStat(1, 'locations_full_prevention');
                    self::notifyAllPlayers('locationFull', clienttranslate('Villains already controlling the Location'), array ());
                } else {
                    self::incStat(1, 'locations_token_added');
                    $marker = self::incGameStateValue('location_marker', 1);
                    self::notifyAllPlayers('locationUpdate', clienttranslate('1 ${location_icon} was added to the Location') . '${source_name}',
                        array (
                            'marker' => $marker,
                            'location_icon' => $this->getLocationIcon(),
                            'source_name' => $sourceName != null ? " ($sourceName)" : ''
                        )
                    );
                    $effects = $this->getActiveEffects(self::$TRIGGER_ON_LOCATION_TOKEN);
                    foreach ($effects as $effectId => $effect) {
                        $this->executeDarkAction($effect['name'], $effect['effect_key']);
                    }
                }
                break;
            case '1dmg_1discard':
                $this->decreaseHealth($activePlayerId, 1);
                $this->setDiscards($activePlayerId, 1);
                self::notifyAllPlayers('updatePlayerStats', clienttranslate('${hero_name} loses 1 ${health_icon} and has to discard 1 card') . '${source_name}',
                    array (
                        'players' => self::getPlayerStats(),
                        'hero_name' => self::getActiveHeroName($activePlayerId),
                        'health_icon' => $this->getHealthIcon(),
                        'source_name' => $sourceName != null ? " ($sourceName)" : ''
                    )
                );
                break;
            default:
                self::notifyAllPlayers('unknown_dark_action', 'Unknown dark action: ' . $action, array ());
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

    function revealDarkArtsCard() {
        self::checkAction("revealDarkArtsCard");
        $location = $this->getLocation();
        $darkArtsCardsRevealed = self::getGameStateValue('dark_arts_cards_revealed');
        $needToRevel = $darkArtsCardsRevealed < $location['dark_arts_cards'];
        if ($needToRevel) {
            self::incStat(1, 'dark_arts_drawn', self::getActivePlayerId());
            $card = $this->darkArtsCards->pickCardForLocation('deck', 'hand');
            $darkArtsCard = $this->darkArtsCardsLibrary->getDarkArtsCard($card['type'], $card['type_arg']);
            self::notifyAllPlayers('darkArtsCardRevealed', clienttranslate('${hero_name} reveals') . ' <b>${dark_arts_card}</b>',
                array(
                    'hero_name' => self::getActiveHeroName(),
                    'dark_arts_card' => $darkArtsCard->name,
                    'darkArtsCard' => $card
                )
            );
            if ($darkArtsCard->effect != null) {
                switch ($darkArtsCard->effect) {
                    case 'no_draw_cards':
                        $this->addEffect('no_draw_cards', self::$TRIGGER_ON_DRAW_CARD, $darkArtsCard->name, self::$SOURCE_DARK_ARTS_CARD, $card['id'], $darkArtsCard->id);
                        break;
                    default:
                        self::notifyAllPlayers('unknown_effect', 'Unknown dark arts effect: ' . $darkArtsCard->effect, array ());
                }
                self::notifyAllPlayers('effects', '', array('effects' => $this->getActiveEffects()));
            }
            if ($darkArtsCard->onPlay != null) {
                $this->executeDarkAction(null, $darkArtsCard->onPlay);
            }

            self::incGameStateValue('dark_arts_cards_revealed', 1);
            $this->gamestate->nextState('revealed');
        } else {
            $this->darkArtsCards->moveAllCardsInLocation('hand', 'discard');
            $this->gamestate->nextState('finished');
        }
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

    function autoplay() {
        self::setGameStateValue('autoplay', 1);
        $this->gamestate->nextState('autoplay');
    }

    function decideEffectOption($option) {
        self::checkAction("decideEffectOption");

        $effects = $this->getEffectById(self::getGameStateValue('effect_id_with_option'));
        $effect = reset($effects);

        $this->executeAction($effect['effect_key'], $option);
        $this->markEffectAsResolved($effect['id']);

        $this->gamestate->nextState();
    }

    function attackVillain($slot, $damage) {
        self::checkAction('attackVillain');

        $playerId = self::getActivePlayerId();
        $attack = $this->getPlayerAttack($playerId);
        if ($attack < $damage) {
            throw new feException('You don\'t have any attack tokens to attack a villain');
        }

        $cards = $this->villainCards->getPlayerHand($slot);
        $card = reset($cards);
        $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);

        $dmg = self::getGameStateValue("villain_${slot}_dmg") + $damage;
        if ($dmg > $villainCard->health) {
            throw new feException('Villain doesn\'t have enough health to suffer ' . $damage . ' damage');
        }

        self::incStat(1, 'villains_damaged', $playerId);
        $this->decreaseAttack($playerId, $damage);

        if ($dmg < $villainCard->health) {
            self::setGameStateValue("villain_${slot}_dmg", $dmg);
            self::notifyAllPlayers(
                'villainAttacked',
                clienttranslate('${hero_name} attacks <b>${villain_name}</b> for ${damage} ${attack_token}'),
                array (
                    'players' => $this->getPlayerStats(),
                    'hero_name' => self::getActiveHeroName($playerId),
                    'villain_name' => $villainCard->name,
                    'damage' => $damage,
                    'attack_token' => $this->getAttackIcon()
                )
            );
            $this->gamestate->nextState('villainAttacked');
        } else {

            $this->removeEffects(self::$SOURCE_VILLAIN, $villainCard->id, null);

            self::setGameStateValue("villain_${slot}_dmg", 0);
            $this->villainCards->moveCard($card['id'], 'discard');
            self::notifyAllPlayers(
                'villainDefeated',
                clienttranslate('${hero_name} defeated') . ' <b>${villain_name}</b>',
                array (
                    'hero_name' => self::getActiveHeroName($playerId),
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

    function discardCard($cardId) {
        $playerId = $this->getCurrentPlayerId();
        $deck = self::getDeck($playerId);
        $card = $deck->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
        if (is_null($card) || $card['location'] != 'hand') {
            throw new feException( "Selected card is not in your hand" );
        }
        self::incStat(1, 'cards_discarded', $playerId);
        $deck->moveCard($cardId, 'discard');

        self::notifyAllPlayers('cardDiscarded', clienttranslate('${hero_name} discards ${card_name}'),
            array(
                'hero_name' => self::getActiveHeroName($playerId),
                'player_id' => $playerId,
                'card_name' => $hogwartsCard->name,
                'card_id' => $cardId,
                'card_played' => $card,
                'players' => $this->getPlayerStats()
            )
        );

        if (self::getGameStateValue('source_is_dark') == 1) {
            $effects = $this->getActiveEffects(self::$TRIGGER_ON_DISCARD);
            foreach ($effects as $effectId => $effect) {
                $this->executeDarkAction($effect['name'], $effect['effect_key']);
            }
        }

        if ($hogwartsCard->onDiscard != null) {
            $this->executeAction($hogwartsCard->onDiscard);
            self::notifyAllPlayers('updatePlayerStats', '', array('players' => $this->getPlayerStats()));
        }

        $this->playerDiscardedACard($playerId);
        if ($this->getDiscards($playerId) == 0) {
            self::notifyPlayer($playerId, 'discardDone', '', array());
            $this->gamestate->setPlayerNonMultiactive($playerId, 'next');
        }
    }

    function acquireHogwartsCard($cardId, $option) {
        self::checkAction("acquireHogwartsCard");
        $playerId = self::getActivePlayerId();
        $card = $this->hogwartsCards->getCard($cardId);
        $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);

        // Check the costs and pay the price
        if ($this->getPlayerInfluence($playerId) < $hogwartsCard->cost) {
            throw new feException('You don\'t have enough influence to acquire that hogwarts card');
        }
        self::incStat($hogwartsCard->cost, 'influence_spent_on_acquire', $playerId);
        $this->decreaseInfluence($playerId, $hogwartsCard->cost);
        self::incStat(1, 'cards_acquired', $playerId);
        if ($hogwartsCard->type == HogwartsCards::$itemType) { self::incStat(1, 'items_acquired', $playerId); }
        if ($hogwartsCard->type == HogwartsCards::$spellType) { self::incStat(1, 'spells_acquired', $playerId); }
        if ($hogwartsCard->type == HogwartsCards::$allyType) { self::incStat(1, 'allies_acquired', $playerId); }

        // Add acquired card to discard pile
        $this->hogwartsCards->moveCard($cardId, 'dev0');

        $putOnTopOfDeck = false;
        if ($option == 1) {
            $effects = $this->getActiveEffects(self::$TRIGGER_ON_ACQUIRE);
            foreach ($effects as $effectId => $effect) {
                $effectKey = $effect['effect_key'];
                if (($effectKey == 'items_top_deck' && $hogwartsCard->type == HogwartsCards::$itemType)
                    || ($effectKey == 'spells_top_deck' && $hogwartsCard->type == HogwartsCards::$spellType)
                    || ($effectKey == 'allies_top_deck' && $hogwartsCard->type == HogwartsCards::$allyType)) {
                    $putOnTopOfDeck = true;
                    break;
                }
            }
        }

        $deck = self::getDeck($playerId);
        $deck->createCards(array($this->hogwartsCardsLibrary->asCard($hogwartsCard)), 'new', $playerId);
        $newCardId = key($deck->getCardsInLocation('new'));
        if ($putOnTopOfDeck) {
            $deck->insertCardOnExtremePosition($newCardId, 'deck', true);
        } else {
            $deck->moveCard($newCardId, 'discard');
        }

        self::notifyAllPlayers(
            'acquireHogwartsCard',
            clienttranslate('${hero_name} acquires ${card_name} for ${card_cost} ${influence_token}'),
            array (
                'players' => $this->getPlayerStats(),
                'acquired_card' => $deck->getCard($newCardId),
                'acquirable_hogwarts_cards' => $this->getAcquirableHogwartsCards($playerId),
                'card_id' => $cardId,
                'new_card_id' => $newCardId,
                'move_card_to_deck' => $putOnTopOfDeck,
                'player_id' => $playerId,
                'hero_name' => self::getActiveHeroName($playerId),
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

    function argRevealDarkArtsCard() {
        $location = $this->getLocation();
        $darkArtsCardsRevealed = self::getGameStateValue('dark_arts_cards_revealed');
        $needToRevel = $darkArtsCardsRevealed < $location['dark_arts_cards'];
        return array('reveal' => $needToRevel);
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

    function argPlayerTurn() {
        $canAutoplay = false;
        $handCards = self::getDeck(self::getActivePlayerId())->getCardsInLocation('hand');
        foreach ($handCards as $cardId => $card) {
            if ($this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg'])->autoplay) {
                $canAutoplay = true;
                break;
            }
        }
        return array(
            'canAutoplay' => $canAutoplay
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
        self::setGameStateInitialValue('autoplay', 0);
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
                            $this->addEffect('max1dmg', self::$TRIGGER_ON_DMG_DARK_ARTS_OR_VILLAIN, $this->getHeroName($heroId), self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $player_id);
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
        self::setGameStateValue('source_is_dark', 1);
        self::setGameStateValue('discard_return_state', self::$STATE_DARK_ARTS);
        $this->gamestate->nextState();
    }

    function stDarkArtsCardRevealed() {
        $playerNeedsDiscard = $this->getPlayersNeedToDiscard();
        if (count($playerNeedsDiscard) > 0) {
            $this->gamestate->nextState('discard');
            return;
        }
        self::setGameStateValue('source_is_dark', 0);
        $stunned = $this->checkStunnedHeroes();
        if ($stunned) {
            $this->gamestate->nextState('discard');
        } else {
            self::setGameStateValue('source_is_dark', 1);
            $this->gamestate->nextState('checksDone');
        }
    }

    function stMultiDiscardCard() {
        $playersNeedToDiscard = array();
        foreach ($this->getPlayersNeedToDiscard() as $playerId => $info) {
            $toDiscard = $info['discards'];
            $handCards = $this->getDeck($playerId)->countCardInLocation('hand');
            if ($toDiscard > $handCards) {
                $this->setDiscards($playerId, $handCards);
            }
            if ($handCards > 0) {
                $playersNeedToDiscard[] = $playerId;
            }
        }
        $this->gamestate->setPlayersMultiactive($playersNeedToDiscard, 'next');
    }

    function stDiscarded() {
        $returnState = self::getGameStateValue('discard_return_state');
        if ($returnState == self::$STATE_DARK_ARTS) {
            $this->gamestate->nextState('darkArts');
        } else if ($returnState == self::$STATE_VILLAINS) {
            $this->gamestate->nextState('villain');
        }
    }

    function stVillainAbilities() {
        self::setGameStateValue('source_is_dark', 1);
        $activeVillainSlot = self::incGameStateValue('villain_turn_slot', 1);
        $villainsMax = self::getGameStateValue('villains_max');
        for($slot = $activeVillainSlot; $slot <= $villainsMax; $slot ++) {
            $cards = $this->villainCards->getPlayerHand($slot);
            $card = reset($cards);
            $villainCard = $this->villainCardsLibrary->getVillainCard($card['type'], $card['type_arg']);
            if ($villainCard->ability != null) {
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
        if ($villainCard->ability != null) {
            $this->executeDarkAction($villainCard->name, $villainCard->ability);
        }

        self::setGameStateValue('source_is_dark', 0);
        self::setGameStateValue('discard_return_state', self::$STATE_VILLAINS);
        $stunned = $this->checkStunnedHeroes();
        if ($stunned) {
            $this->gamestate->nextState('discard');
        } else {
            $this->gamestate->nextState('executed');
        }
    }

    function stBeforePlayerTurn() {
        self::setGameStateValue('source_is_dark', 0);
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

        self::notifyAllPlayers('cardPlayed', clienttranslate('${hero_name} plays ${card_name}'),
            array(
                'hero_name' => self::getActiveHeroName($playerId),
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
            $this->removeEffects(self::$SOURCE_HOGWARTS_CARD, $cardId, $playerId);
            $effectsModified = true;
        }

        if ($hogwartsCard->onPlayEffect != null) {
            switch ($hogwartsCard->onPlayEffect) {
                case 'c[+2hp_any_onDefVil]':
                    $this->addEffect('c[+2hp_any]', self::$TRIGGER_ON_DEFEAT_VILLAIN, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $playerId);
                    break;
                case '+1inf_onDefVil':
                    $this->addEffect('+1inf', self::$TRIGGER_ON_DEFEAT_VILLAIN, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $playerId);
                    break;
                case 'spells_top_deck':
                    $this->addEffect('spells_top_deck', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $playerId);
                    break;
                case 'items_top_deck':
                    $this->addEffect('items_top_deck', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $playerId);
                    break;
                case 'allies_top_deck':
                    $this->addEffect('allies_top_deck', self::$TRIGGER_ON_ACQUIRE, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId, $playerId);
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

        if (self::getGameStateValue('autoplay') == 1) {
            $this->gamestate->nextState("autoplay");
        } else {
            $this->gamestate->nextState("playerTurn");
        }
    }

    function stAutoplay() {
        $handCards = self::getDeck(self::getActivePlayerId())->getCardsInLocation('hand');
        foreach ($handCards as $cardId => $card) {
            if ($this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg'])->autoplay) {
                self::setGameStateValue('played_card_id', $cardId);
                $this->gamestate->nextState('playCard');
                return;
            }
        }
        self::setGameStateValue('autoplay', 0);
        $this->gamestate->nextState('playerTurn');
    }
    
    function stVillainAttacked() {
        // Just a step to update UI
        $this->gamestate->nextState();
    }

    function stVillainDefeated() {
        self::incStat(1, 'villains_defeated');
        self::incStat(1, 'villains_defeated', self::getActivePlayerId());
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
            if (!$executionComplete) {
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
        self::incStat(1, 'turns_number', $playerId);
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
            clienttranslate('${hero_name} ends the turn'),
            array (
                'players' => self::getPlayerStats(),
                'new_hogwarts_cards' => $newHogwartsCards,
                'player_id' => $playerId,
                'hero_name' => self::getActiveHeroName($playerId),
            )
        );
        $this->gamestate->nextState();
    }

    function stEndOfTurnActions() {
        if ($this->isLocationFull()) {
            $this->gamestate->nextState('revealLocation');
        } else if ($this->villainCards->countCardInLocation('deck') > 0 && $this->villainCards->countCardInLocation('hand') < self::getGameStateValue('villains_max')) {
            $this->gamestate->nextState('revealVillain');
        } else {
            $this->gamestate->nextState('refillHandCards');
        }
    }

    function stRevealLocation() {
        self::incStat(1, 'locations_lost');
        $locationNumber = self::getGameStateValue('location_number');
        $locationTotal = self::getGameStateValue('location_total');
        if ($locationNumber < $locationTotal) {
            $locationNumber ++;
            self::setGameStateValue('location_number', $locationNumber);
            self::setGameStateValue('location_marker', self::getGameStateValue('location_reveal_marker'));

            self::notifyAllPlayers('locationRevealed', '<b>' . clienttranslate('New Location revealed') . '</b>',
                array (
                    'location_number' => $locationNumber,
                    'location_marker_total' => $this->locations[self::getGameStateValue('game_number')][$locationNumber]['max_tokens'],
                    'location_marker' => self::getGameStateValue('location_marker')
                )
            );

            $this->gamestate->nextState('revealed');
        } else {
            $this->gamestate->nextState('gameLost');
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
        $this->recoverStunnedHeroes();

        $playerId = self::getActivePlayerId();
        $deck = self::getDeck($playerId);

        $newHandCards = $deck->pickCards(5, 'deck', $playerId);

        self::notifyPlayer($playerId, 'refillHandCards', '', array('new_hand_cards' => $newHandCards));
        self::notifyAllPlayers(
            'refillHandCardsLog',
            clienttranslate('${hero_name} draws 5 new cards'),
            array ('hero_name' => self::getActiveHeroName($playerId), 'players' => self::getPlayerStats())
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
