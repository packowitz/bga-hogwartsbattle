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
 * stats.inc.php
 *
 * HogwartsBattle game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "villains_defeated" => array("id"=> 10,
                    "name" => totranslate("Villains defeated"),
                    "type" => "int" ),

        "locations_lost" => array("id"=> 11,
                    "name" => totranslate("Locations lost"),
                    "type" => "int" ),

        "locations_token_added" => array("id"=> 12,
                    "name" => totranslate("Tokens added to Location"),
                    "type" => "int" ),

        "locations_token_removed" => array("id"=> 13,
                    "name" => totranslate("Tokens removed from Location"),
                    "type" => "int" ),

        "locations_full_prevention" => array("id"=> 14,
                    "name" => totranslate("Tokens not added to full Location"),
                    "type" => "int" ),


/*
        "table_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("table test stat 1"), 
                                "type" => "int" ),
                                
        "table_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("table test stat 2"), 
                                "type" => "float" )
*/  
    ),
    
    // Statistics existing for each player
    "player" => array(

        "hero" => array("id"=> 50,
                    "name" => totranslate("Hero"),
                    "type" => "int" ),

        "turns_number" => array("id"=> 51,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),

        "gained_influence" => array("id"=> 60,
                    "name" => totranslate("Gained influence"),
                    "type" => "int" ),

        "influence_spent_on_acquire" => array("id"=> 61,
                    "name" => totranslate("Influence spent to acquire cards"),
                    "type" => "int" ),

        "cards_acquired" => array("id"=> 62,
                    "name" => totranslate("Cards acquired"),
                    "type" => "int" ),

        "items_acquired" => array("id"=> 63,
                    "name" => totranslate("Items acquired"),
                    "type" => "int" ),

        "spells_acquired" => array("id"=> 64,
                    "name" => totranslate("Spells acquired"),
                    "type" => "int" ),

        "allies_acquired" => array("id"=> 65,
                    "name" => totranslate("Allies acquired"),
                    "type" => "int" ),

        "gained_attack" => array("id"=> 70,
                    "name" => totranslate("Gained attack"),
                    "type" => "int" ),

        "villains_damaged" => array("id"=> 71,
                    "name" => totranslate("Villains damaged"),
                    "type" => "int" ),

        "villains_defeated" => array("id"=> 72,
                    "name" => totranslate("Villains defeated"),
                    "type" => "int" ),

        "healed_self" => array("id"=> 80,
                    "name" => totranslate("Healed self"),
                    "type" => "int" ),

        "healed_others" => array("id"=> 81,
                    "name" => totranslate("Healed others"),
                    "type" => "int" ),

        "healed_by_others" => array("id"=> 82,
                    "name" => totranslate("Healed by others"),
                    "type" => "int" ),

        "health_lost" => array("id"=> 83,
                    "name" => totranslate("Health lost"),
                    "type" => "int" ),

        "stunned" => array("id"=> 90,
                    "name" => totranslate("Stunned"),
                    "type" => "int" ),

        "dark_arts_drawn" => array("id"=> 91,
                    "name" => totranslate("Dark Arts cards"),
                    "type" => "int" ),

        "cards_discarded" => array("id"=> 100,
                    "name" => totranslate("Cards discarded"),
                    "type" => "int" ),

        "cards_drawn" => array("id"=> 101,
                    "name" => totranslate("Extra cards drawn"),
                    "type" => "int" ),

        "locations_token_removed" => array("id"=> 110,
                    "name" => totranslate("Tokens removed from Location"),
                    "type" => "int" ),
    
/*
        Examples:    
        
        
        "player_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("player test stat 1"), 
                                "type" => "int" ),
                                
        "player_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("player test stat 2"), 
                                "type" => "float" )

*/    
    ),

    "value_labels" => array(
        50 => array(
            1 => totranslate("Harry"),
            2 => totranslate("Ron"),
            3 => totranslate("Hermione"),
            4 => totranslate("Neville")
        ),
    )

);
