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
 * states.inc.php
 *
 * HogwartsBattle game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game development by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 10)
    ),

    10 => array(
        "name" => "initTurn",
        "description" => "",
        "type" => "game",
        "action" => "stInitTurn",
        "transitions" => array("" => 19)
    ),
    19 => array(
        "name" => "beforePlayerTurn",
        "description" => "",
        "type" => "game",
        "action" => "stBeforePlayerTurn",
        "transitions" => array("" => 20)
    ),

    20 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play cards or end the turn'),
        "descriptionmyturn" => clienttranslate('${you} must play cards or end your turn'),
        "type" => "activeplayer",
        "possibleactions" => array("playCard", "acquireHogwartsCard", "endTurn"),
        "transitions" => array("playCard" => 21, "acquireHogwartsCard" => 20, "endTurn" => 70)
    ),
    21 => array(
        "name" => "playCard",
        "description" => "",
        "type" => "game",
        "action" => "stPlayCard",
        "transitions" => array("cardResolved" => 20, "chooseCardOption" => 22)
    ),
    22 => array(
        "name" => "chooseCardOption",
        "description" => clienttranslate('${actplayer} decides on card options'),
        "descriptionmyturn" => clienttranslate('${you} must decide'),
        "type" => "activeplayer",
        "args" => "argChooseCardOption",
        "possibleactions" => array("decidePlayCardOption"),
        "transitions" => array("" => 21)
    ),
    70 => array(
        "name" => "endTurn",
        "description" => "",
        "type" => "game",
        "action" => "stEndTurn",
        "transitions" => array("" => 75)
    ),
    75 => array(
        "name" => "refillHandCards",
        "description" => "",
        "type" => "game",
        "action" => "stRefillHandCards",
        "transitions" => array("" => 76)
    ),
    76 => array(
        "name" => "cleanEffectsNextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stCleanEffectsNextPlayer",
        "transitions" => array("" => 20)
    ),

    /*
        Examples:

        2 => array(
            "name" => "nextPlayer",
            "description" => '',
            "type" => "game",
            "action" => "stNextPlayer",
            "updateGameProgression" => true,
            "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
        ),

        10 => array(
            "name" => "playerTurn",
            "description" => clienttranslate('${actplayer} must play a card or pass'),
            "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
            "type" => "activeplayer",
            "possibleactions" => array( "playCard", "pass" ),
            "transitions" => array( "playCard" => 2, "pass" => 2 )
        ),

    */

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



