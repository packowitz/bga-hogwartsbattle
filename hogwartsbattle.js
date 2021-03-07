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

            this.villainsMax = 0;
            this.villainsLeft = 0;
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

            this.hogwartsCardDescriptions = gamedatas.hogwarts_cards_descriptions;
            this.gameNr = gamedatas.game_number;

            this.locationTotal = gamedatas.location_total;
            $('location_total').innerHTML = this.locationTotal;

            this.locationNr = gamedatas.location_number;
            this.location_counter = new ebg.counter();
            this.location_counter.create('location_number');
            this.location_counter.setValue(this.locationNr);

            this.locationMarkerTotal = gamedatas.location_marker_total;
            $('location_marker_total').innerHTML = this.locationMarkerTotal;

            this.locationMarker = gamedatas.location_marker;
            this.location_marker_counter = new ebg.counter();
            this.location_marker_counter.create('location_marker');
            this.location_marker_counter.setValue(this.locationMarker);

            dojo.place(
              this.format_block( 'jstpl_location', {
                  elementId: 'location_image_' + this.locationNr,
                  posX: (this.locationNr - 1) * 187.5,
                  posY: (this.gameNr - 1) * 140,
              }), 'location_image');

            // Villains
            this.villainsMax = gamedatas.villains_max;
            this.villainsLeft = gamedatas.villains_left;
            this.villainCounter = new ebg.counter();
            this.villainCounter.create('villain_counter');
            this.villainCounter.setValue(this.villainsLeft);

            let villainDeckElem = dojo.byId('villain_deck');
            if (this.villainsLeft == 0) {
                dojo.removeClass(villainDeckElem, 'villain_back');
                dojo.addClass(villainDeckElem, 'villain_back_empty');
            }

            for (let i = 1; i <= this.villainsMax; i++) {
                dojo.place(
                  this.format_block( 'jstpl_active_villain', {
                      villainNr: i
                  }), 'active_villains');
                let villainCard = gamedatas['villain_' + i];
                let dmg = gamedatas['villain_' + i + '_dmg'];
                this.villainDmgCounter[i] = new ebg.counter();
                this.villainDmgCounter[i].create('damage_counter_v' + i);
                this.villainDmgCounter[i].setValue(dmg);

                this.villainDropZones[i] = new ebg.zone();
                this.villainDropZones[i].create(this, 'villain_drop_zone_v' + i, 15, 15);
                this.villainDropZones[i].setPattern('ellipticalfit');

                // TODO place dmg x attack tokens to drop zone

                this.placeVillain(villainCard, i);
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

            switch (stateName) {
                case 'initTurnEffects': {
                    console.log(args);
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
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName ) {
                case 'playerTurn': {
                    this.clearAcquirableHogwartsCards();
                    if (this.isCurrentPlayerActive()) {
                        // remove can_play indicator and handle
                        this.extractCards(this.playerHand).forEach(card => {
                            dojo.removeClass(dojo.byId(card.id), 'can_play');
                            this.disconnect( $(card.id), 'onclick');
                        });
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
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                    case 'playerTurn': {
                        this.addActionButton('endTurnId', 'End turn', 'onEndTurn');
                        break;
                    }
                    case 'chooseCardOption': {
                        for (let option in args) {
                            let optId = option.substr('option_'.length);
                            this.addActionButton(option, args[option], (evt) => { this.onDecideOnPlayHandCard(evt, optId); });
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
            dojo.place(
              this.format_block( 'jstpl_active_villain_image', {
                  elementId: 'villain_' + villainId,
                  villainId: villainId,
                  slot: slot,
                  posX: -200 * cardNr,
                  posY: 150 * gameNr,
              }), 'active_villain_' + slot);
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
                    if (this.isCurrentPlayerActive()) {
                        let elementId = 'hogwarts_card_' + card.id + '_p' + this.player_id;
                        dojo.addClass(dojo.byId(elementId), 'can_play');
                        this.connect( $(elementId), 'onclick', 'onPlayHandCard');
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

        textToIconSubstitute: function(text) {
            if (!text) {
                return '';
            }
            return dojo.string.substitute(text, {
                influence_token: this.getIcon('influence'),
                attack_token: this.getIcon('attack'),
                health_icon: this.getIcon('health')
            });
        },

        getIcon: function(type) {
            switch (type) {
                case 'influence': return '<div class="icon influence_icon"></div>';
                case 'attack': return '<div class="icon attack_icon"></div>';
                case 'health': return '<div class="icon health_icon"></div>';
                case 'card': return '<div class="icon hand_cards_icon"></div>';
                case 'onHand': return '<div class="icon on_hand_icon"></div>';
                case 'onDiscard': return '<div class="icon on_discard_icon"></div>';
                case 'onAcquire': return '<div class="icon on_acquire_icon"></div>';
                case 'onDefeatVillain': return '<div class="icon on_defeat_villain_icon"></div>';
                case 'onDmgDarkArtsOrVillain': return '<div class="icon on_loose_health_icon"></div>';
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
            this.addHogwartsCardTooltip('active_effect_' + effect.id, effect.source_card_id);

        },

        removeActiveEffect: function(effectId) {
            dojo.destroy('active_effect_' + effectId);
            let idx = this.visibleEffectIds.indexOf(effectId);
            if (idx >= 0) {
                this.visibleEffectIds.splice(idx, 1);
            }
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

        onEndTurn: function (evt) {
            dojo.stopEvent(evt);
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

        onDecideOnPlayHandCard: function(evt, cardOption) {
            let action = 'decidePlayCardOption';
            if (this.checkAction(action, true)) {
                this.ajaxcall(`/${this.game_name}/${this.game_name}/${action}.html`, {
                    option : cardOption,
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
            dojo.subscribe('refillHandCards', this, "notif_refillHandCards");
            dojo.subscribe('refillHandCardsLog', this, "notif_refillHandCardsLog");
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            dojo.subscribe('acquireHogwartsCard', this, "notif_acquireHogwartsCard");
        },
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

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

        notif_refillHandCards: function(notif) {
            setTimeout(() => this.drawHogwartsCards(notif.args.new_hand_cards), 1000);
        },

        notif_refillHandCardsLog: function(notif) {
            this.updatePlayerStats(notif.args.players);
        },

        notif_cardPlayed: function(notif) {
            this.acquirableHogwartsCards = notif.args.acquirable_hogwarts_cards;
            this.checkAcquirableHogwartsCards();
            this.checkActiveEffects(notif.args.effects);

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
            this.updatePlayerStats(notif.args.players);
        },

        notif_acquireHogwartsCard: function(notif) {
            this.acquirableHogwartsCards = notif.args.acquirable_hogwarts_cards;
            this.checkAcquirableHogwartsCards();

            let cardElemId = 'hogwarts_card_' + notif.args.card_id;

            // clean up can_acquire
            if (this.isCurrentPlayerActive()) {
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
   });
});
