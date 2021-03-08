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


<div class="whiteblock hogwarts_background">
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
        <div class="whiteblock default_background">
            <div id="active_effects" class="active_effects">
                <h3 style="margin-right: 5px;">{EFFECTS}</h3>
            </div>
        </div>
        <div id="played_cards_wrapper" class="whiteblock hogwarts_background">
            <h3>{BOARD}</h3>
            <div class="card_height">
                <div id="played_cards"></div>
            </div>
        </div>
        <div id="dark_arts_events_wrapper" class="whiteblock hogwarts_background" style="display: none;">
            <h3>{DARK_ARTS}</h3>
            <div class="card_height">
                <div id="dark_arts_events"></div>
            </div>
        </div>
        <div style="display: flex; flex-wrap: wrap;">
            <div class="whiteblock default_background big_card_wrapper">
                <h3 class="full-width flex-space-between">
                    <div>{LOCATION} <span id="location_number"></span>/<span id="location_total"></span></div>
                    <div><div class="icon location_icon"></div><span id="location_marker"></span>/<span id="location_marker_total"></span></div>
                </h3>
                <div id="location_image"></div>
            </div>
            <div class="whiteblock default_background big_card_wrapper">
                <h3 class="flex-space-between">
                    <div>{VILLAIN_CARDS}</div>
                    <div id="villain_counter"></div>
                </h3>
                <div id="villain_deck" class="villain_back"></div>
            </div>
            <div id="active_villains" style="display: flex;"></div>
        </div>
    </div>

    <div class="whiteblock revealed_hogwarts_cards_wrapper default_background">
        <h3>{HOGWARTS_CARDS}</h3>
        <div id="hogwarts_cards" class="revealed_hogwarts_cards"></div>
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
        <div class="player_stat"><div class="icon health_icon" id="health_icon_p${id}"></div><span id="health_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="icon attack_icon" id="attack_icon_p${id}"></div><span id="attack_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="icon influence_icon" id="influence_icon_p${id}"></div><span id="influence_stat_p${id}">0</span></div>\
        <div class="player_stat"><div class="icon hand_cards_icon" id="hand_cards_icon_p${id}"></div><span id="hand_cards_stat_p${id}">0</span></div>\
    </div>';

  var jstpl_hogwarts_card = '<div class="hogwarts_card" id="${elementId}" data-card-id="${cardId}" data-card-type-id="${cardTypeId}" style="background-position: ${posX}% ${posY}%; position: absolute; top: 0; left: 0;"></div>';

  var jstpl_hogwarts_card_tooltip =
    '<div class="hogwarts_card_tooltip">\
        <h3>${cardName}</h3>\
        <div class="separator_line" style="margin-bottom: 4px;"></div>\
        ${description}\
        <div class="hogwarts_card_large" style="margin: 4px; background-position: ${posX}% ${posY}%;"></div>\
    </div>';

  var jstpl_tooltip_text = '<div style="display: flex; align-items: center; flex-wrap: wrap; max-width: 200px; margin-top: 4px;"><div>${text}</div></div>';

  var jstpl_active_effect =
    '<div class="active_effect" id="${elementId}" data-effect-id="${effectId}">\
        ${icon}\
        <div>${effectName}</div>\
    </div>';

  var jstpl_location = '<div id="${elementId}" class="location_card" style="background-position: ${posX}px ${posY}px;"></div>';

  var jstpl_active_villain =
    '<div class="whiteblock default_background big_card_wrapper" style="height: auto">\
        <h3 class="flex-space-between">\
            <div>{ACTIVE_VILLAIN}</div>\
            <div><div class="icon attack_icon"></div><span id="damage_counter_v${villainNr}"></span></div>\
        </h3>\
        <div class="active_villain_background">\
            <div id="active_villain_${villainNr}" class="active_villain_wrapper"></div>\
            <div id="villain_drop_zone_v${villainNr}" data-villain-slot="${villainNr}" class="active_hero_drop_zone"></div>\
        </div>\
    </div>';

  var jstpl_active_villain_image = '<div class="active_villain" id="${elementId}" data-villain-id="${villainId}" data-villain-slot="${slot}" style="background-position: ${posX}px ${posY}px;"></div>';

  var jstpl_villain_damage = '<div id="${elementId}" class="large_attack_icon" style="position: absolute; top: 0; left: 0;"></div>';

</script>

{OVERALL_GAME_FOOTER}
