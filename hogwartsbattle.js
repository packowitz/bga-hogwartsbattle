/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * HogwartsBattle implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * hogwartsbattle.js
 *
 * HogwartsBattle user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
      "dojo", "dojo/_base/declare",
      "ebg/core/gamegui",
      "ebg/counter",
      "ebg/zone"
  ],
function (dojo, declare) {
    return declare("bgagame.hogwartsbattle", ebg.core.gamegui, {
        constructor: function(){
            console.log('hogwartsbattle constructor');
            this.currentState = '';
            this.currentPlayerId = '';

            this.cardwidth = 200;
            this.cardheight = 280;
            this.cardsPerRow = 16;

            this.health_counters = {};
            this.attack_counters = {};
            this.influence_counters = {};
            this.handCards_counters = {};

            this.hogwartsCardDescriptions = {};

            this.gameNr = 0;
            this.locationTotal = 0;
            this.locationNr = 0;
            this.location_counter = {};
            this.locationMarkerTotal = 0;
            this.locationMarker = 0;
            this.location_marker_counter = {};

            this.villainDescriptions = {};
            this.villainsMax = 0;
            this.villains = {};
            this.villainCounter = {};
            this.villainDmgCounter = {};
            this.villainDropZones = {};

            this.discard_piles = {};

            this.acquirableHogwartsCards = [];
            this.visibleEffectIds = [];
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            this.currentPlayerId = this.player_id;

            this.gameNr = gamedatas.game_number;
            this.hogwartsCardDescriptions = gamedatas.hogwarts_cards_descriptions;
            this.villainDescriptions = gamedatas.villain_descriptions;
            this.darkArtsDescriptions = gamedatas.dark_arts_descriptions;

            this.locationTotal = gamedatas.location_total;
            $('location_total').innerHTML = this.locationTotal;

            this.placeLocationCard(gamedatas.location_number);
            this.location_counter = new ebg.counter();
            this.location_counter.create('location_number');
            this.location_counter.setValue(this.locationNr);

            this.locationMarkerTotal = gamedatas.location_marker_total;
            $('location_marker_total').innerHTML = this.locationMarkerTotal;

            this.locationMarker = gamedatas.location_marker;
            this.location_marker_counter = new ebg.counter();
            this.location_marker_counter.create('location_marker');
            this.location_marker_counter.setValue(this.locationMarker);

            for (let i = 1; i <= this.locationMarker; i++) {
                this.placeMarkerToLocation(i);
            }

            // Villains
            this.villainsMax = gamedatas.villains_max;
            let villainsLeft = gamedatas.villains_left;
            this.villainCounter = new ebg.counter();
            this.villainCounter.create('villain_counter');
            this.villainCounter.setValue(villainsLeft);

            let villainDeckElem = dojo.byId('villain_deck');
            if (villainsLeft == 0) {
                dojo.removeClass(villainDeckElem, 'villain_back');
                dojo.addClass(villainDeckElem, 'villain_back_empty');
            }

            for (let slot = 1; slot <= this.villainsMax; slot++) {
                dojo.place(
                  this.format_block( 'jstpl_active_villain', {
                      villainNr: slot
                  }), 'active_villains');

                this.villainDmgCounter[slot] = new ebg.counter();
                this.villainDmgCounter[slot].create('damage_counter_v' + slot);

                this.villainDropZones[slot] = new ebg.zone();
                this.villainDropZones[slot].create(this, 'villain_drop_zone_v' + slot, 40, 40);
                this.villainDropZones[slot].setPattern('ellipticalfit');

                let villainCard = gamedatas['villain_' + slot];
                if (villainCard) {
                    let villainId = (parseInt(villainCard.type) * 100) + parseInt(villainCard.type_arg);
                    this.villains[slot] = this.villainDescriptions[villainId];

                    let dmg = gamedatas['villain_' + slot + '_dmg'];
                    this.villainDmgCounter[slot].setValue(dmg);

                    for (let i = 1; i <= dmg; i++) {
                        this.placeAttackTokenToVillain(slot, i);
                    }

                    this.placeVillain(villainCard, slot);
                } else {
                    this.villains[slot] = null;
                }
            }

            // Setting up player boards
            for(var player_id in gamedatas.players)
            {
                var player = gamedatas.players[player_id];
                         
                var player_board_div = $('player_board_' + player_id);
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );

                // add tooltips
                this.addTooltip('health_icon_p' + player_id, _('Health (0-10)'), '' );
                this.addTooltip('attack_icon_p' + player_id, _('Attack tokens'), '' );
                this.addTooltip('influence_icon_p' + player_id, _('Influence tokens'), '' );
                this.addTooltip('hand_cards_icon_p' + player_id, _('Hand cards'), '' );

                // create counter per player
                this.health_counters[player_id] = new ebg.counter();
                this.health_counters[player_id].create('health_stat_p' + player_id);
                this.health_counters[player_id].setValue(player.health);

                this.attack_counters[player_id] = new ebg.counter();
                this.attack_counters[player_id].create('attack_stat_p' + player_id);
                this.attack_counters[player_id].setValue(player.attack);

                this.influence_counters[player_id] = new ebg.counter();
                this.influence_counters[player_id].create('influence_stat_p' + player_id);
                this.influence_counters[player_id].setValue(player.influence);

                this.handCards_counters[player_id] = new ebg.counter();
                this.handCards_counters[player_id].create('hand_cards_stat_p' + player_id);
                this.handCards_counters[player_id].setValue(player.hand_card_count);

                // discard pile
                this.discard_piles[player_id] = new ebg.zone();
                this.discard_piles[player_id].create(this, 'player_discard_' + player_id, this.cardwidth * 0.5, this.cardheight * 0.5);

                for (let card_discarded_id in player.discard_cards) {
                    let card_discarded = player.discard_cards[card_discarded_id];
                    this.placeHogwartsCard(card_discarded, this.discard_piles[player_id], 'player_discard_' + player_id, player_id);
                }
            }

            // Dark Arts cards
            this.darkArtsCards = new ebg.zone();
            this.darkArtsCards.create(this, $('dark_arts_events'), this.cardheight * 0.5, this.cardheight * 0.5);

            let toggledView = false;
            for (let cardIdx in gamedatas.dark_arts_cards) {
                if (!toggledView) {
                    dojo.style('played_cards_wrapper', 'display', 'none');
                    dojo.style('dark_arts_events_wrapper', 'display', 'inherit');
                }
                let card = gamedatas.dark_arts_cards[cardIdx];
                this.placeDarkArtsCard(card);
            }
            
            // Hogwarts cards
            this.hogwartsCards = new ebg.zone();
            this.hogwartsCards.create(this, $('hogwarts_cards'), this.cardwidth * 0.5, this.cardheight * 0.5);

            // Played cards
            this.playedCards = new ebg.zone();
            this.playedCards.create(this, $('played_cards'), this.cardwidth * 0.5, this.cardheight * 0.5);

            // Player hand
            this.playerHand = new ebg.zone();
            this.playerHand.create(this, $('myhand'), this.cardwidth * 0.5, this.cardheight * 0.5);

            for (let cardIdx in gamedatas.played_cards) {
                let card = gamedatas.played_cards[cardIdx];
                this.placeHogwartsCard(card, this.playedCards, 'played_cards', gamedatas.active_player);
            }

            this.revealHogwartsCards(gamedatas.hogwarts_cards);
            this.drawHogwartsCards(gamedatas.hand);
            this.acquirableHogwartsCards = gamedatas.acquirable_hogwarts_cards;
            this.checkActiveEffects(gamedatas.effects);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args) {
            console.log('Entering state: ' + stateName);
            this.currentState = stateName;

            switch (stateName) {
                case 'revealDarkArtsCard':
                    this.currentPlayerId = this.getActivePlayerId();
                case 'initTurn':
                    dojo.style('played_cards_wrapper', 'display', 'none');
                    dojo.style('dark_arts_events_wrapper', 'display', 'inherit');
                    break;
                case 'beforePlayerTurn':
                    this.darkArtsCards.removeAll();
                    dojo.style('played_cards_wrapper', 'display', 'inherit');
                    dojo.style('dark_arts_events_wrapper', 'display', 'none');
                    break;
                case 'initTurnEffects': {
                    for (let idx in args.args) {
                        let effect = args.args[idx];
                        this.addActiveEffect(effect);
                    }
                    break;
                }
                case 'playerTurn': {
                    if (this.isCurrentPlayerActive()) {
                        this.extractCards(this.playerHand).forEach(card => {
                            let cardNode = dojo.byId(card.id);
                            if (!dojo.hasClass(cardNode, 'can_play')) {
                                dojo.addClass(cardNode, 'can_play');
                                this.connect($(card.id), 'onclick', 'onPlayHandCard');
                            }
                        });
                        this.checkAcquirableHogwartsCards();

                        // can attack villains
                        if (this.attack_counters[this.player_id].getValue() > 0) {
                            for (let i = 1; i <= this.villainsMax; i++) {
                                if (this.villains[i]) {
                                    dojo.addClass(dojo.byId('villain_drop_zone_v' + i), 'can_attack');
                                    this.connect($('villain_drop_zone_v' + i), 'onclick', 'onAttackVillain');
                                }
                            }
                        }
                    }
                    break;
                }
                case 'villainAttacked': {
                    for (let slot in args.args) {
                        if (slot <= this.villainsMax) {
                            let dmg = args.args[slot];
                            let dmgBefore = this.villainDmgCounter[slot].getValue();
                            let dmgDiff = dmg - dmgBefore;
                            this.villainDmgCounter[slot].incValue(dmgDiff);
                            if (dmgDiff > 0) {
                                for (let i = (dmgBefore + 1); i <= dmg; i++) {
                                    this.placeAttackTokenToVillain(slot, i);
                                }
                            }
                        }
                    }
                    break;
                }
                case 'endTurn': {
                    this.acquirableHogwartsCards = [];
                    break;
                }
                case 'cleanEffectsNextPlayer': {
                    this.checkActiveEffects([]);
                    break;
                }
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName)
        {
            console.log('Leaving state: ' + stateName);
            
            switch(stateName) {
                case 'discardCard': {
                    if (this.isCurrentPlayerActive()) {
                        this.extractCards(this.playerHand).forEach(card => {
                            dojo.removeClass(dojo.byId(card.id), 'can_discard');
                            this.disconnect($(card.id), 'onclick');
                        });
                    }
                    break;
                }
                case 'playerTurn': {
                    this.clearAcquirableHogwartsCards();
                    if (this.isCurrentPlayerActive()) {
                        // remove can_play indicator and handle
                        this.extractCards(this.playerHand).forEach(card => {
                            dojo.removeClass(dojo.byId(card.id), 'can_play');
                            this.disconnect($(card.id), 'onclick');
                        });

                        // remove villain drop zone indicator
                        for (let i = 1; i <= this.villainsMax; i++) {
                            dojo.removeClass(dojo.byId('villain_drop_zone_v' + i), 'can_attack');
                            this.disconnect($('villain_drop_zone_v' + i), 'onclick');
                        }
                    }
                    break;
                }
            }
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log('onUpdateActionButtons: ' + stateName);
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'revealDarkArtsCard': {
                        let label = args['reveal'] ? 'Reveal' : 'Done';
                        this.addActionButton('revealId', _(label), 'onRevealDarkArtsCard');
                        break;
                    }
                    case 'playerTurn': {
                        if (args['canAutoplay']) {
                            this.addActionButton('autoplayButton', _('Autoplay'), 'onAutoplay');
                            this.addTooltip('autoplayButton', _('Plays all simple hand cards in any order. Cards with choices, draw card abilities or when the order matters will not get played.'), '' );
                        }
                        this.addActionButton('endTurnId', _('End turn'), 'onEndTurn', null, false, 'red');
                        break;
                    }
                    case 'chooseCardOption': {
                        for (let option in args) {
                            let optId = option.substr('option_'.length);
                            this.addActionButton(option, args[option], (evt) => { this.onDecideOnPlayHandCard(evt, optId); });
                        }
                        break;
                    }
                    case 'chooseEffectOption': {
                        for (let option in args) {
                            let optId = option.substr('option_'.length);
                            this.addActionButton(option, args[option], (evt) => { this.onDecideOnEffectOption(evt, optId); });
                        }
                        break;
                    }
                    case 'discardCard': {
                        if (this.isCurrentPlayerActive()) {
                            this.extractCards(this.playerHand).forEach(card => {
                                let cardNode = dojo.byId(card.id);
                                if (!dojo.hasClass(cardNode, 'can_discard')) {
                                    dojo.addClass(cardNode, 'can_discard');
                                    this.connect($(card.id), 'onclick', 'onDiscardHandCard');
                                }
                            });
                        }
                        break;
                    }
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        updatePlayerStats(players) {
            if (players) {
                for (let playerId in players) {
                    let player = players[playerId];

                    let healthDiff = player.health - this.health_counters[playerId].getValue();
                    this.health_counters[playerId].incValue(healthDiff);

                    let attackDiff = player.attack - this.attack_counters[playerId].getValue();
                    this.attack_counters[playerId].incValue(attackDiff);

                    let influenceDiff = player.influence - this.influence_counters[playerId].getValue();
                    this.influence_counters[playerId].incValue(influenceDiff);

                    let handCardsDiff = player.hand_card_count - this.handCards_counters[playerId].getValue();
                    this.handCards_counters[playerId].incValue(handCardsDiff);

                    // update discard pile
                    let currentDiscardIds = [];
                    for (let cardIdx in player.discard_cards) {
                        currentDiscardIds.push(player.discard_cards[cardIdx].id);
                    }
                    // remove cards from discard pile that are not there anymore
                    let removedElemIds = [];
                    this.extractCards(this.discard_piles[playerId]).forEach(card => {
                        let cardNode = dojo.byId(card.id);
                        if(!currentDiscardIds.includes(cardNode.dataset.cardId)) {
                            dojo.addClass(cardNode, 'to_be_removed');
                            removedElemIds.push(card.id);
                            this.discard_piles[playerId].removeFromZone(card.id, true);
                        }
                    });
                    // add missing cards to discard pile
                    for (let cardIdx in player.discard_cards) {
                        let card = player.discard_cards[cardIdx];
                        let cardElemId = 'hogwarts_card_' + card.id + '_p' + playerId;
                        if (!dojo.byId(cardElemId)) {
                            this.placeHogwartsCard(card, this.discard_piles[playerId], 'player_discard_' + playerId, playerId);
                        }
                    }
                }
            }
        },

        extractCards: function(zone) {
            let cards = [];
            for (let cardIdx in zone.getAllItems()) {
                cards.push(zone.items[cardIdx]);
            }
            return cards;
        },

        revealHogwartsCards: function(hogwartsCards) {
            if (hogwartsCards) {
                for (let i in hogwartsCards) {
                    let card = hogwartsCards[i];
                    this.placeHogwartsCard(card, this.hogwartsCards, 'hogwarts_cards');
                }
            }
        },

        placeLocationCard: function(locationNr) {
            this.locationNr = locationNr;
            let elementId = 'location_image_' + locationNr;
            dojo.place(
              this.format_block('jstpl_location', {
                  elementId: elementId,
                  posX: (locationNr - 1) * 200,
                  posY: (this.gameNr - 1) * 150,
              }), 'overall_player_board_' + this.currentPlayerId);

            this.attachToNewParent(elementId, 'location_image');
            this.slideToObjectPos(elementId, 'location_image', 0, 0 ).play();
        },

        placeHogwartsCard: function(card, zone, zoneElemId, playerId) {
            let elementId = 'hogwarts_card_' + card.id;
            if (playerId) {
                elementId += '_p' + playerId;
            }
            let gameNr = parseInt(card.type);
            let cardNr = parseInt(card.type_arg);
            let cardTypeId = (gameNr * 100) + cardNr;
            dojo.place(
              this.format_block( 'jstpl_hogwarts_card', {
                  elementId: elementId,
                  cardId: card.id,
                  cardTypeId: cardTypeId,
                  posX: -100 * cardNr,
                  posY: 100 * gameNr,
              }), zoneElemId);
            zone.placeInZone(elementId);
            this.addHogwartsCardTooltip(elementId, cardTypeId);
        },

        placeVillain: function(card, slot) {
            let gameNr = parseInt(card.type);
            let cardNr = parseInt(card.type_arg);
            let villainId = (gameNr * 100) + cardNr;
            $('villain_health_v' + slot).innerHTML = this.villainDescriptions[villainId].health;
            dojo.place(
              this.format_block( 'jstpl_active_villain_image', {
                  elementId: 'villain_' + villainId,
                  villainId: villainId,
                  slot: slot,
                  posX: -200 * cardNr,
                  posY: 150 * gameNr,
              }), 'villain_deck');
            this.attachToNewParent('villain_' + villainId, 'active_villain_' + slot);
            this.slideToObject('villain_' + villainId, 'active_villain_' + slot, 1000).play();
            this.addVillainCardTooltip('villain_' + villainId, villainId);
        },

        placeAttackTokenToVillain: function(slot, dmg) {
            let elementId = 'dmg_' + slot + '_' + dmg;
            dojo.place(this.format_block( 'jstpl_villain_damage', {
                elementId: elementId
            }), 'overall_player_board_' + this.currentPlayerId);
            this.villainDropZones[slot].placeInZone(elementId);
        },

        placeDarkArtsCard: function(card) {
            let gameNr = parseInt(card.type);
            let cardNr = parseInt(card.type_arg);
            let cardTypeId = (gameNr * 100) + cardNr;
            let elementId = 'dark_arts_card_' + card.id;
            dojo.place(
              this.format_block('jstpl_dark_arts_card', {
                  elementId: elementId,
                  posX: -140 * cardNr,
                  posY: 140 * gameNr,
              }), 'overall_player_board_' + this.currentPlayerId);
            this.darkArtsCards.placeInZone(elementId);
            this.addDarkArtsCardTooltip(elementId, cardTypeId);
        },

        removeAttackTokenToVillain: function(slot, dmg) {
            let elementId = 'dmg_' + slot + '_' + dmg;
            this.villainDropZones[slot].removeFromZone(elementId);
            this.fadeOutAndDestroy(elementId);
        },

        clearAcquirableHogwartsCards: function() {
            this.extractCards(this.hogwartsCards).forEach(card => {
                let cardNode = dojo.byId(card.id);
                if (dojo.hasClass(cardNode, 'can_acquire')) {
                    dojo.removeClass(cardNode, 'can_acquire');
                    this.disconnect( $(card.id), 'onclick');
                }
            });
        },

        checkAcquirableHogwartsCards: function() {
            if (this.acquirableHogwartsCards) {
                this.extractCards(this.hogwartsCards).forEach(card => {
                    let cardNode = dojo.byId(card.id);
                    let cardId = parseInt(cardNode.dataset.cardId);
                    if (dojo.hasClass(cardNode, 'can_acquire')) {
                        if (!this.acquirableHogwartsCards.includes(cardId)) {
                            dojo.removeClass(cardNode, 'can_acquire');
                            this.disconnect( $(card.id), 'onclick');
                        }
                    } else {
                        if (this.acquirableHogwartsCards.includes(cardId)) {
                            dojo.addClass(cardNode, 'can_acquire');
                            this.connect( $(card.id), 'onclick', 'onAcquireHogwartsCard');
                        }
                    }
                });
            } else {
                this.clearAcquirableHogwartsCards();
            }
        },

        drawHogwartsCards: function(hogwartsCards) {
            if (hogwartsCards) {
                for (let idx in hogwartsCards) {
                    let card = hogwartsCards[idx];
                    this.placeHogwartsCard(card, this.playerHand, 'myhand', this.player_id);
                    if (this.isCurrentPlayerActive() && this.currentState == 'playCard') {
                        let elementId = 'hogwarts_card_' + card.id + '_p' + this.player_id;
                        dojo.addClass(dojo.byId(elementId), 'can_play');
                        this.connect( $(elementId), 'onclick', 'onPlayHandCard');
                    } else {
                        console.log('drawn card not marked as can_play because of: isCurrentPlayer: ' + this.isCurrentPlayerActive() + ' currentState: ' + this.currentState);
                    }
                }
            }
        },

        addHogwartsCardTooltip: function(elementId, typeId) {
            let cardDesc = this.hogwartsCardDescriptions[typeId];
            let description = this.format_block('jstpl_tooltip_text', {
                text: this.textToIconSubstitute(cardDesc.desc['onPlay'])
            });
            for (var descKey in cardDesc.desc) {
                if (descKey != 'onPlay') {
                    description += this.format_block('jstpl_tooltip_text', {
                        text: this.textToIconSubstitute(cardDesc.desc[descKey])
                    });
                }
            }
            this.addTooltipHtml(elementId, this.format_block('jstpl_hogwarts_card_tooltip', {
                cardName: cardDesc.name,
                description: description,
                posX: -100 * cardDesc.cardNr,
                posY: 100 * cardDesc.gameNr,
            }));
        },

        addVillainCardTooltip: function(elementId, villainId) {
            let cardDesc = this.villainDescriptions[villainId];
            this.addTooltipHtml(elementId, this.format_block('jstpl_villain_tooltip', {
                villainName: cardDesc.name,
                description: this.textToIconSubstitute(cardDesc.desc),
                posX: -375 * cardDesc.cardNr,
                posY: 280 * cardDesc.gameNr,
            }));
        },

        addDarkArtsCardTooltip: function(elementId, cardType) {
            let cardDesc = this.darkArtsDescriptions[cardType];
            this.addTooltipHtml(elementId, this.format_block('jstpl_dark_arts_tooltip', {
                cardName: cardDesc.name,
                description: this.textToIconSubstitute(cardDesc.desc),
                posX: -280 * cardDesc.cardNr,
                posY: 280 * cardDesc.gameNr,
            }));
        },

        textToIconSubstitute: function(text) {
            if (!text) {
                return '';
            }
            return dojo.string.substitute(text, {
                influence_token: this.getIcon('influence'),
                attack_token: this.getIcon('attack'),
                health_icon: this.getIcon('health'),
                location_token: this.getIcon('location')
            });
        },

        getIcon: function(type) {
            switch (type) {
                case 'influence': return '<div class="icon influence_icon"></div>';
                case 'attack': return '<div class="icon attack_icon"></div>';
                case 'health': return '<div class="icon health_icon"></div>';
                case 'card': return '<div class="icon hand_cards_icon"></div>';
                case 'location': return '<div class="icon location_icon"></div>';
                case 'onHand': return '<div class="icon on_hand_icon"></div>';
                case 'onDrawCard': return '<div class="icon on_draw_card"></div>';
                case 'onDiscard': return '<div class="icon on_discard_icon"></div>';
                case 'onAcquire': return '<div class="icon on_acquire_icon"></div>';
                case 'onDefeatVillain': return '<div class="icon on_defeat_villain_icon"></div>';
                case 'onDmgDarkArtsOrVillain': return '<div class="icon on_loose_health_icon"></div>';
                case 'onLocationToken': return '<div class="icon on_location_token"></div>';
            }
        },

        checkActiveEffects: function(effects) {
            if (effects) {
                // remove effects not in place anymore
                let effectIds = [];
                for (idx in effects) {
                    effectIds.push(effects[idx].id);
                }
                let effectIdsToRemove = [];
                this.visibleEffectIds.forEach(effectId => {
                    if (!effectIds.includes(effectId)) {
                        effectIdsToRemove.push(effectId);
                    }
                });
                effectIdsToRemove.forEach(effectId => this.removeActiveEffect(effectId));

                // add missing effects
                for (idx in effects) {
                    let effect = effects[idx];
                    if (!this.visibleEffectIds.includes(effect.id)) {
                        this.addActiveEffect(effect);
                    }
                }
            }
        },

        addActiveEffect: function(effect) {
            dojo.place(
              this.format_block('jstpl_active_effect', {
                  elementId: 'active_effect_' + effect.id,
                  effectId: effect.id,
                  effectName: effect.name,
                  icon: this.getIcon(effect.effect_trigger),
              }), 'active_effects');
            this.visibleEffectIds.push(effect.id);
            if (effect.source == 'hogwartsCard') {
                this.addHogwartsCardTooltip('active_effect_' + effect.id, effect.source_card_id);
            } else if (effect.source == 'villain') {
                this.addVillainCardTooltip('active_effect_' + effect.id, effect.source_id);
            } else if (effect.source == 'darkArtsCard') {
                this.addDarkArtsCardTooltip('active_effect_' + effect.id, effect.source_card_id);
            }

        },

        removeActiveEffect: function(effectId) {
            dojo.destroy('active_effect_' + effectId);
            let idx = this.visibleEffectIds.indexOf(effectId);
            if (idx >= 0) {
                this.visibleEffectIds.splice(idx, 1);
            }
        },

        updateLocationMarker: function(marker) {
            let markerBefore = this.location_marker_counter.getValue();
            let diff = marker - markerBefore;
            this.location_marker_counter.incValue(diff);
            if (diff > 0) {
                for (let i = (markerBefore + 1); i <= marker; i++) {
                    this.placeMarkerToLocation(i);
                }
            }
            if (diff < 0) {
                for (let i = markerBefore; i > marker; i--) {
                    this.removeMarkerFromLocation(i);
                }
            }
        },

        placeMarkerToLocation: function(nr) {
            let elementId = 'location_' + this.locationMarkerTotal + '_' + nr;
            dojo.place(this.format_block( 'jstpl_location_token', {
                elementId: elementId
            }), 'overall_player_board_' + this.currentPlayerId);
            let locationPosId = 'location_pos_' + this.locationMarkerTotal + '_' + nr;
            this.attachToNewParent(elementId, locationPosId);
            this.slideToObject(elementId, locationPosId, 1000).play();
        },

        removeMarkerFromLocation: function(nr) {
            let elementId = 'location_' + this.locationMarkerTotal + '_' + nr;
            this.slideToObjectAndDestroy(elementId, "player_boards", 1000, 0 );
        },

        playerHasActionsLeft: function() {
            return dojo.query('.can_play').length > 0
              || dojo.query('.can_acquire').length > 0
              || dojo.query('.can_attack').length > 0;
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        onRevealDarkArtsCard: function (evt) {
            dojo.stopEvent(evt);
            let action = 'revealDarkArtsCard';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onAutoplay: function(e) {
            dojo.stopEvent(e);
            let action = 'autoplay';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onEndTurn: function(evt) {
            dojo.stopEvent(evt);
            if (this.playerHasActionsLeft()) {
                this.confirmationDialog( _('Are you sure to end your turn? You have actions left. Like hand cards, attack tokens or enough influence tokens to acquire another hogwarts card.'),
                  dojo.hitch(this, function() { this.doEndTurn(); })
                );
            } else {
                this.doEndTurn();
            }
        },

        doEndTurn: function() {
            let action = 'endTurn';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onAcquireHogwartsCard: function(e) {
            dojo.stopEvent(e);
            let cardId = parseInt(e.target.dataset.cardId);
            console.log('acquire hogwarts card ' + cardId);
            let action = 'acquireHogwartsCard';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    id : cardId,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onPlayHandCard: function(e) {
            dojo.stopEvent(e);
            let cardId = parseInt(e.target.dataset.cardId);
            console.log('play hand card ' + cardId);
            let action = 'playCard';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    id : cardId,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onDiscardHandCard: function(e) {
            dojo.stopEvent(e);
            let cardId = parseInt(e.target.dataset.cardId);
            console.log('discard hand card ' + cardId);
            let action = 'discard';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    id : cardId,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onDecideOnPlayHandCard: function(evt, cardOption) {
            let action = 'decidePlayCardOption';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    option : cardOption,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onDecideOnEffectOption: function(evt, cardOption) {
            let action = 'decideEffectOption';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    option : cardOption,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        onAttackVillain: function(evt) {
            dojo.stopEvent(evt);
            let slot = parseInt(evt.target.dataset.villainSlot);
            console.log('attack villain ' + slot);
            let action = 'attackVillain';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    slot : slot,
                    lock : true
                }, this, function(result) {}, function(is_error) {});
            }
        },

        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/hogwartsbattle/hogwartsbattle/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your hogwartsbattle.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //
            dojo.subscribe('endTurn', this, "notif_endTurn");
            dojo.subscribe('refillHandCards', this, "notif_newHandCards");
            dojo.subscribe('newHandCards', this, "notif_newHandCards");
            dojo.subscribe('refillHandCardsLog', this, "notif_refillHandCardsLog");
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            dojo.subscribe('cardDiscarded', this, "notif_cardDiscarded");
            dojo.subscribe('acquireHogwartsCard', this, "notif_acquireHogwartsCard");
            dojo.subscribe('villainAttacked', this, "notif_updatePlayerStats");
            dojo.subscribe('villainDefeated', this, "notif_villainDefeated");
            dojo.subscribe('villainRevealed', this, "notif_villainRevealed");
            dojo.subscribe('updatePlayerStats', this, "notif_updatePlayerStats");
            dojo.subscribe('effects', this, "notif_updateEffects");
            dojo.subscribe('darkArtsCardRevealed', this, "notif_darkArtsCardRevealed");
            dojo.subscribe('locationUpdate', this, "notif_locationUpdate");
            dojo.subscribe('locationRevealed', this, "notif_locationRevealed");
            dojo.subscribe('acquirableHogwartsCards', this, "notif_acquirableHogwartsCards");
            dojo.subscribe('discardDone', this, "notif_discardDone");
            dojo.subscribe('important', this, "notif_important");
        },

        notif_endTurn: function(notif) {
            // discard played cards
            this.extractCards(this.playedCards).forEach(card => {
                this.playedCards.removeFromZone(card.id);
                this.discard_piles[notif.args.player_id].placeInZone(card.id);
            });

            if (this.player_id == notif.args.player_id) {
                // discard unplayed hand cards
                this.extractCards(this.playerHand).forEach(card => {
                    this.playerHand.removeFromZone(card.id);
                    this.discard_piles[notif.args.player_id].placeInZone(card.id);
                });
            }
            this.updatePlayerStats(notif.args.players);
            this.revealHogwartsCards(notif.args.new_hogwarts_cards);
        },

        notif_newHandCards: function(notif) {
            setTimeout(() => this.drawHogwartsCards(notif.args.new_hand_cards), 1000);
        },

        notif_refillHandCardsLog: function(notif) {
            this.updatePlayerStats(notif.args.players);
        },

        notif_cardPlayed: function(notif) {
            if (this.player_id == notif.args.player_id) {
                let cardElemId = 'hogwarts_card_' + notif.args.card_id + '_p' + notif.args.player_id;
                this.playerHand.removeFromZone(cardElemId);
                let cardNode = dojo.byId(cardElemId);
                dojo.removeClass(cardNode, 'can_play');
                this.disconnect( $(cardElemId), 'onclick');
                this.playedCards.placeInZone(cardElemId);
                this.addHogwartsCardTooltip(cardElemId, parseInt(cardNode.dataset.cardTypeId));
            } else {
                this.placeHogwartsCard(notif.args.card_played, this.playedCards, 'overall_player_board_' + notif.args.player_id, notif.args.player_id);
            }
        },

        notif_cardDiscarded: function(notif) {
            if (this.player_id == notif.args.player_id) {
                let cardElemId = 'hogwarts_card_' + notif.args.card_id + '_p' + notif.args.player_id;
                this.playerHand.removeFromZone(cardElemId);
                let cardNode = dojo.byId(cardElemId);
                dojo.removeClass(cardNode, 'can_discard');
                this.disconnect( $(cardElemId), 'onclick');
                this.discard_piles[notif.args.player_id].placeInZone(cardElemId);
                this.addHogwartsCardTooltip(cardElemId, parseInt(cardNode.dataset.cardTypeId));
            } else {
                this.placeHogwartsCard(notif.args.card_played, this.discard_piles[notif.args.player_id], 'overall_player_board_' + notif.args.player_id, notif.args.player_id);
            }
            this.updatePlayerStats(notif.args.players);
        },

        notif_acquireHogwartsCard: function(notif) {

            let cardElemId = 'hogwarts_card_' + notif.args.card_id;

            if (this.isCurrentPlayerActive()) {
                this.acquirableHogwartsCards = notif.args.acquirable_hogwarts_cards;
                this.checkAcquirableHogwartsCards();

                // clean up can_acquire on acquired card
                this.disconnect( $(cardElemId), 'onclick');
                dojo.removeClass(dojo.byId(cardElemId), 'can_acquire');
            }

            // move acquired hogwarts card to players discard pile
            this.hogwartsCards.removeFromZone(cardElemId);
            let cardElem = dojo.byId(cardElemId);
            let newElemId = 'hogwarts_card_' + notif.args.new_card_id + '_p' + notif.args.player_id;
            dojo.setAttr(cardElem, 'id', newElemId);
            dojo.setAttr(cardElem, 'data-card-id', notif.args.new_card_id);
            this.discard_piles[notif.args.player_id].placeInZone(newElemId);
            this.addHogwartsCardTooltip(newElemId, parseInt(cardElem.dataset.cardTypeId));

            // update player stats with timeout to make sure the acquired card is in discard pile
            setTimeout(() => this.updatePlayerStats(notif.args.players), 1000);
        },

        notif_updatePlayerStats: function(notif) {
            this.updatePlayerStats(notif.args.players)
        },

        notif_updateEffects: function(notif) {
            this.checkActiveEffects(notif.args.effects);
        },

        notif_acquirableHogwartsCards: function(notif) {
            this.acquirableHogwartsCards = notif.args.acquirable_hogwarts_cards;
            this.checkAcquirableHogwartsCards();
        },

        notif_villainDefeated: function(notif) {
            this.checkActiveEffects(notif.args.effects);
            let slot = notif.args.villain_slot;
            let villainId = notif.args.villain_id;
            this.villains[slot] = null;
            let dmgBefore = this.villainDmgCounter[slot].getValue();
            this.villainDmgCounter[slot].setValue(0);
            for (let i = 1; i <= dmgBefore; i++) {
                this.removeAttackTokenToVillain(slot, i);
            }
            this.slideToObjectAndDestroy("villain_" + villainId, "player_boards", 1000, 0 );
        },

        notif_villainRevealed: function(notif) {
            let slot = notif.args.villain_slot;
            let villainCard = notif.args.villain;

            let villainId = (parseInt(villainCard.type) * 100) + parseInt(villainCard.type_arg);
            this.villains[slot] = this.villainDescriptions[villainId];

            this.placeVillain(villainCard, slot);

            this.villainCounter.incValue(-1);
            if (this.villainCounter.getValue() == 0) {
                let villainDeckElem = dojo.byId('villain_deck');
                dojo.removeClass(villainDeckElem, 'villain_back');
                dojo.addClass(villainDeckElem, 'villain_back_empty');
            }
        },

        notif_darkArtsCardRevealed: function(notif) {
            let darkArtsCard = notif.args.darkArtsCard;
            this.placeDarkArtsCard(darkArtsCard);
        },

        notif_locationUpdate: function(notif) {
            this.updateLocationMarker(notif.args.marker);
        },

        notif_locationRevealed: function(notif) {
            this.fadeOutAndDestroy('location_image_' + this.locationNr);
            this.updateLocationMarker(0);

            this.locationMarkerTotal = notif.args.location_marker_total;
            $('location_marker_total').innerHTML = this.locationMarkerTotal;
            this.location_marker_counter.setValue(notif.args.location_marker);
            this.location_counter.incValue(1);

            this.placeLocationCard(notif.args.location_number);
        },

        notif_discardDone: function(notif) {
            this.extractCards(this.playerHand).forEach(card => {
                dojo.removeClass(dojo.byId(card.id), 'can_discard');
                this.disconnect($(card.id), 'onclick');
            });
        },

        notif_important: function(notif) {
            this.showMessage(notif.args.message, 'error');
        }
   });
});
