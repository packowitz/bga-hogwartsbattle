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
 * gameoptions.inc.php
 *
 * HogwartsBattle game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in hogwartsbattle.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    /*
    
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
                'name' => totranslate('my game option'),    
                'values' => array(

                            // A simple value for this option:
                            1 => array( 'name' => totranslate('option 1') )

                            // A simple value for this option.
                            // If this value is chosen, the value of "tmdisplay" is displayed in the game lobby
                            2 => array( 'name' => totranslate('option 2'), 'tmdisplay' => totranslate('option 2') ),

                            // Another value, with other options:
                            //  description => this text will be displayed underneath the option when this value is selected to explain what it does
                            //  beta=true => this option is in beta version right now (there will be a warning)
                            //  alpha=true => this option is in alpha version right now (there will be a warning, and starting the game will be allowed only in training mode except for the developer)
                            //  nobeginner=true  =>  this option is not recommended for beginners
                            3 => array( 'name' => totranslate('option 3'), 'description' => totranslate('this option does X'), 'beta' => true, 'nobeginner' => true )
                        ),
                'default' => 1
            ),

    */

    101 => array(
        'name' => totranslate('Game'),
        'values' => array(
            1 => array('name' => totranslate('Game 1')),
//            2 => array('name' => totranslate('Game 2')),
//            3 => array('name' => totranslate('Game 3')),
//            4 => array('name' => totranslate('Game 4')),
//            5 => array('name' => totranslate('Game 5')),
//            6 => array('name' => totranslate('Game 6')),
//            7 => array('name' => totranslate('Game 7'))
        ),
        'default' => 1
    ),
    103 => array(
        'name' => totranslate('Marker on first Location'),
        'values' => array(
            0 => array('name' => totranslate('0 - Easy')),
            1 => array('name' => totranslate('1 - Advanced')),
            2 => array('name' => totranslate('2 - Expert')),
            3 => array('name' => totranslate('3 - Suicide'))
        ),
        'default' => 0
    ),
    104 => array(
        'name' => totranslate('Marker on new Location'),
        'values' => array(
            0 => array('name' => totranslate('0 - Easy')),
            1 => array('name' => totranslate('1 - Advanced')),
            2 => array('name' => totranslate('2 - Expert')),
            3 => array('name' => totranslate('3 - Suicide'))
        ),
        'default' => 0
    ),
    105 => array(
        'name' => totranslate('Hero selection'),
        'values' => array(
            0 => array('name' => totranslate('Random')),
            1 => array('name' => totranslate('Select'))
        ),
        'default' => 0
    ),
    108 => array(
        'name' => totranslate('Allow replacing Hogwarts cards'),
        'values' => array(
            0 => array('name' => totranslate('Not allowed'), 'description' => totranslate('Basic rule: Players are not allowed to replace Hogwarts cards')),
            1 => array('name' => totranslate('Allowed'), 'description' => totranslate('Rule from first expansion: Instead of acquiring a Hogwarts card, active player may replace all Hogwarts cards'))
        ),
        'default' => 0
    ),

);


