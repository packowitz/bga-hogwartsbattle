{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- HogwartsBattle implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    hogwartsbattle_hogwartsbattle.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<div class="whiteblock">
    <div class="player_board">
        <div class="player_board_part" style="flex-grow: 1;">
            <h3>{MY_HAND}</h3>
            <div class="full-width card_height">
                <div id="myhand"></div>
            </div>
        </div>
    </div>

</div>

<div class="game_board">
    <div class="flex-grow">
        <div class="whiteblock">
            <div id="active_effects" class="active_effects">
                <h3 style="margin-right: 5px;">{EFFECTS}</h3>
            </div>
        </div>
        <div id="played_cards_wrapper" class="whiteblock">
            <h3>{BOARD}</h3>
            <div class="card_height">
                <div id="played_cards"></div>
            </div>
        </div>
        <div id="dark_arts_events_wrapper" class="whiteblock" style="display: none;">
            <h3>{DARK_ARTS}</h3>
            <div class="card_height">
                <div id="dark_arts_events"></div>
            </div>
        </div>
    </div>

    <div class="whiteblock revealed_hogwarts_cards">
        <h3>{HOGWARTS_CARDS}</h3>
        <div id="acquired_hogwarts_card" class="acquired_hogwarts_card"></div>
        <div id="hogwarts_cards"></div>
    </div>
</div>



<!-- BEGIN player -->
<div class="whiteblock">
    <h3 style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
    <div class="card_height">
        <div id="player_discard_{PLAYER_ID}" class="discard_pile"></div>
    </div>
</div>
<!-- END player -->


<script type="text/javascript">

  // Javascript HTML templates

  /*
  // Example:
  var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

  var jstpl_player_board =
    '<div class="player_stats">\
        <div class="player_stat">${hero_name}</div>\
        <div class="player_stat"><div class="health_icon" id="health_icon_p${id}"></div><span id="health_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="attack_icon" id="attack_icon_p${id}"></div><span id="attack_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="influence_icon" id="influence_icon_p${id}"></div><span id="influence_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="hand_cards_icon" id="hand_cards_icon_p${id}"></div><span id="hand_cards_stat_p${id}">0</span></div>\
    </div>';

  var jstpl_hogwarts_card = '<div class="hogwarts_card" id="${elementId}" data-card-id="${cardId}" style="background-position: ${posX}% ${posY}%; position: absolute; top: 0; left: 0;"></div>';

  var jstpl_hogwarts_card_tooltip =
    '<div class="hogwarts_card_tooltip">\
        <div>${cardName}</div>\
    </div>';

  var jstpl_active_effect =
    '<div class="active_effect" id="${elementId}" data-effect-id="${effectId}">\
        <div class="effect_icon" style="background-position: ${iconX}% ${iconY}%;"></div>\
        <div>${effectName}</div>\
    </div>';

</script>

{OVERALL_GAME_FOOTER}
