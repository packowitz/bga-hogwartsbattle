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


class HogwartsBattle extends Table
{
    private static $TRIGGER_ON_HAND = 'onHand';
    private static $TRIGGER_ON_DISCARD = 'onDiscard';
    private static $TRIGGER_ON_ACQUIRE = 'onAcquire';
    private static $TRIGGER_ON_DEFEAT_VILLAIN = 'onDefeatVillain';
    private static $TRIGGER_ON_DMG_DARK_ARTS_OR_VIALLAIN = 'onDmgDarkArtsOrVillain';

    private static $SOURCE_HOGWARTS_CARD = 'hogwartsCard';

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
            //    "location_markers" => 10,
            //    "location_markers_max" => 11,
            //    "villain_slots" => 12,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );

        $this->hogwartsCardsLibrary = new HogwartsCards();

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

        // Init global values with their initial values
        self::setGameStateInitialValue('played_card_id', 0);
        self::setGameStateInitialValue('play_card_option', 0);

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

    function getActiveEffects() {
        $sql = "SELECT effect_id id, effect_key, effect_trigger, effect_name name, effect_source source, effect_source_id source_id, effect_source_card_id source_card_id FROM effect";
        return self::getCollectionFromDb($sql);
    }

    function addEffect($effect_key, $effect_trigger, $name, $source, $source_id, $source_card_id) {
        self::DbQuery("INSERT INTO effect(effect_key, effect_trigger, effect_name, effect_source, effect_source_id, effect_source_card_id) VALUES('$effect_key', '$effect_trigger', '$name', '$source', '$source_id', '$source_card_id');");
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

    function getPlayerInfluence($playerId) {
        return self::getUniqueValueFromDB('select player_influence from player where player_id = ' . $playerId);
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

    function gainInfluence($playerId, $gain) {
        self::DbQuery("UPDATE player set player_influence = player_influence + ${gain} where player_id = ${playerId}");
    }

    function gainAttack($playerId, $gain) {
        self::DbQuery("UPDATE player set player_attack = player_attack + ${gain} where player_id = ${playerId}");
    }

    function gainHealth($playerId, $gain) {
        self::DbQuery("UPDATE player set player_health = player_health + ${gain} where player_id = ${playerId}");
    }

    function gainHealthByHeroId($heroId, $gain) {
        self::DbQuery("UPDATE player set player_health = player_health + ${gain} where player_hero = ${heroId}");
    }

    function getDeck($playerId) {
        return $this->heroDecks[$this->getHeroId($playerId)];
    }

    function getLogsGainHealthIcon() {
        return '<div class="icon health_icon"></div>';
    }

    function getLogsGainInfluenceIcon() {
        return '<div class="icon influence_icon"></div>';
    }

    function getLogsGainAttackIcon() {
        return '<div class="icon attack_icon"></div>';
    }

    function getLogsDrawCardIcon() {
        return '<div class="icon hand_cards_icon"></div>';
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in hogwartsbattle.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    function endTurn() {
        self::checkAction("endTurn");
        $this->gamestate->nextState('endTurn');
    }

    function playCard($cardId) {
        self::checkAction("playCard");
        self::setGameStateValue('played_card_id', $cardId);
        $this->gamestate->nextState('playCard');
    }

    function playCardOption($card_option) {
        self::checkAction("decidePlayCardOption");
        self::setGameStateValue('play_card_option', $card_option);
        $this->gamestate->nextState();
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
                'i18n' => array ('card_name'),
                'players' => $this->getPlayerStats(),
                'acquired_card' => $deck->getCard($newCardId),
                'card_id' => $cardId,
                'new_card_id' => $newCardId,
                'player_id' => $playerId,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $hogwartsCard->name,
                'card_cost' => $hogwartsCard->cost,
                'influence_token' => $this->getLogsGainInfluenceIcon()
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

        // get choice action
        $choiceAction = '';
        foreach ($hogwartsCard->onPlay as $action) {
            if (substr($action, 0, 2) == 'c[') {
                $choiceAction = $action;
            }
        }

        $args = array();
        switch ($choiceAction) {
            case 'c[+1att|+2hp]':
                $args['option_1'] = '+1 ' . $this->getLogsGainAttackIcon();
                $args['option_2'] = '+2 ' . $this->getLogsGainHealthIcon();
                break;
            case 'c[+2inf|+1inf_all]':
                $args['option_1'] = '+2 ' . $this->getLogsGainInfluenceIcon();
                $args['option_2'] = clienttranslate('ALL Heroes') . ' +1 ' . $this->getLogsGainInfluenceIcon();
                break;
            case 'c[+1att|+2hp_any]':
                $args['option_9'] = '+1 ' . $this->getLogsGainAttackIcon();
                foreach ($this->getHeroIdsInGame() as $heroId => $whatever) {
                    $args["option_${heroId}"] = $this->getHeroName($heroId) . ' +2 ' . $this->getLogsGainHealthIcon();
                }
                break;
        }

        return $args;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stInitTurn() {

        // check hand cards for onHand effects
        $playerHeroes = self::getCollectionFromDB("SELECT player_id id, player_hero heroId FROM player", true);
        foreach ($playerHeroes as $player_id => $heroId) {
            $handCards = $this->heroDecks[$heroId]->getCardsInLocation('hand');
            foreach ($handCards as $cardId => $card) {
                $hogwartsCard = $this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg']);
                foreach ($hogwartsCard->onHand as $onHandEffect) {
                    switch ($onHandEffect) {
                        case 'max1dmg':
                            $this->addEffect('max1dmg', self::$TRIGGER_ON_DMG_DARK_ARTS_OR_VIALLAIN, $this->getHeroName($heroId), self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                            break;
                    }
                }
            }
        }

        // TODO check villains and horcrux effects

        $this->gamestate->nextState();
    }

    function stInitTurnEffects() {
        // nothing to do here. Meaning of this step is to get the args method called to have the current effects visible in the ui
        $this->gamestate->nextState();
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

        // Execute card
        $executionComplete = true;
        $notif_log = clienttranslate('${player_name} plays ${card_name}');
        $notif_args = array(
            'i18n' => array ('card_name'),
            'player_name' => self::getActivePlayerName(),
            'player_id' => $playerId,
            'card_name' => $hogwartsCard->name,
            'card_id' => $cardId,
            'card_played' => $card,
        );
        foreach ($hogwartsCard->onPlay as $action) {
            switch ($action) {
                case '+1inf':
                    $this->gainInfluence($playerId, 1);
                    $notif_log .= clienttranslate(' and gains 1 ${influence_token}');
                    $notif_args['influence_token'] = $this->getLogsGainInfluenceIcon();
                    break;
                case '+1att':
                    $this->gainAttack($playerId, 1);
                    $notif_log .= clienttranslate(' and gains 1 ${attack_token}.');
                    $notif_args['attack_token'] = $this->getLogsGainAttackIcon();
                    break;
                case '+1att_xAllyPlayed':
                    $attack = 0;
                    foreach ($deck->getCardsInLocation('played') as $playedCardId => $playedCard) {
                        if ($this->hogwartsCardsLibrary->getCard($card['type'], $card['type_arg'])->type == HogwartsCards::$allyType) {
                            $attack ++;
                        }
                    }
                    $this->gainAttack($playerId, $attack);
                    $notif_log .= clienttranslate(' and gains ${attack} ${attack_token}.');
                    $notif_args['attack'] = $attack;
                    $notif_args['attack_token'] = $this->getLogsGainAttackIcon();
                    break;
                case '+1inf_onDefVil':
                    $this->addEffect('+1inf', self::$TRIGGER_ON_DEFEAT_VILLAIN, $hogwartsCard->name, self::$SOURCE_HOGWARTS_CARD, $cardId, $hogwartsCard->typeId);
                    $notif_args['influence_token'] = $this->getLogsGainInfluenceIcon();
                    break;
                case 'c[+1att|+2hp]':
                    $option = self::getGameStateValue('play_card_option');
                    if ($option == 0) {
                        $executionComplete = false;
                    } else if ($option == 1) {
                        $this->gainAttack($playerId, 1);
                        $notif_log .= clienttranslate(' and gains 1 ${attack_token}.');
                        $notif_args['attack_token'] = $this->getLogsGainAttackIcon();
                    } else {
                        $healing = min(array(10 - $this->getHealth($playerId), 2));
                        $this->gainHealth($playerId, $healing);
                        $notif_log .= clienttranslate(' and gains ${healing} ${health_icon}.');
                        $notif_args['healing'] = $healing;
                        $notif_args['health_icon'] = $this->getLogsGainHealthIcon();
                    }
                    break;
                case 'c[+2inf|+1inf_all]':
                    $option = self::getGameStateValue('play_card_option');
                    if ($option == 0) {
                        $executionComplete = false;
                    } else if ($option == 1) {
                        $this->gainInfluence($playerId, 1);
                        $notif_log .= clienttranslate(' and gains 1 ${influence_token}.');
                        $notif_args['influence_token'] = $this->getLogsGainInfluenceIcon();
                    } else {
                        foreach (self::loadPlayersBasicInfos() as $pId => $info) {
                            $this->gainInfluence($pId, 1);
                        }
                        $notif_log .= clienttranslate(' and ALL Heroes gain 1 ${influence_token}.');
                        $notif_args['influence_token'] = $this->getLogsGainInfluenceIcon();
                    }
                    break;
                case 'c[+1att|+2hp_any]':
                    $option = self::getGameStateValue('play_card_option');
                    if ($option == 0) {
                        $executionComplete = false;
                    } else if ($option == 9) {
                        $this->gainAttack($playerId, 1);
                        $notif_log .= clienttranslate(' and gains 1 ${attack_token}.');
                        $notif_args['attack_token'] = $this->getLogsGainAttackIcon();
                    } else {
                        $healing = min(array(10 - $this->getHealthByHeroId($option), 2));
                        $this->gainHealthByHeroId($option, $healing);
                        $notif_log .= clienttranslate(' and ${hero_name} gains ${healing} ${health_icon}.');
                        $notif_args['hero_name'] = $this->getHeroName($option);
                        $notif_args['healing'] = $healing;
                        $notif_args['health_icon'] = $this->getLogsGainHealthIcon();
                    }
                    break;
                default:
                    $notif_log .= ' Oh no, this action is not implemented yet. (' . $action . ')';
                    break;
            }
        }

        if ($executionComplete == true) {
            $deck->moveCard($cardId, 'played');
            $notif_args['players'] = $this->getPlayerStats();
            $notif_args['acquirable_hogwarts_cards'] = $this->getAcquirableHogwartsCards($playerId);
            $notif_args['effects'] = $this->getActiveEffects();
            self::notifyAllPlayers(
                'cardPlayed',
                $notif_log,
                $notif_args
            );

            // reset game states
            self::setGameStateInitialValue('played_card_id', 0);
            self::setGameStateInitialValue('play_card_option', 0);

            $this->gamestate->nextState('cardResolved');
        } else {
            $this->gamestate->nextState('chooseCardOption');
        }
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
        // TODO send hand cards only to player
        // TODO Reset all effects that are in place for this turn
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
