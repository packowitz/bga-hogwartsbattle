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

            this.discard_piles = {};

            this.acquirableHogwartsCards = [];
            this.visibleEffectIds = [];

            // this.handCardsZone = new ebg.zone();
            // this.handCardsZone.create(this, 'myhand', 100, 140);
            // this.handCardsZone.setPattern('horizontalfit');
            //
            // this.discardZone = new ebg.zone();
            // this.discardZone.create(this, 'played_cards', 100, 140);
            // this.discardZone.setPattern('verticalfit');
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
            
            // TODO: Set up your game interface here, according to "gamedatas"

            var customStyle = document.createElement('style');
            customStyle.type = 'text/css';
            customStyle.innerHTML = `.card_size_50p { background-size: ${this.cardsPerRow * this.cardwidth * 0.5}px; }`;
            document.getElementsByTagName('head')[0].appendChild(customStyle);

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
            console.log('acquirable hogwarts cards');
            console.log(this.acquirableHogwartsCards);
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
            console.log( 'onUpdateActionButtons: '+stateName );
                      
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
                    case 'playerTurn':
                        this.addActionButton( 'endTurnId', 'End turn', 'onEndTurn' );
                        break;
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
                    console.log('reveal hogwarts card');
                    console.log(card);
                    this.placeHogwartsCard(card, this.hogwartsCards, 'hogwarts_cards');
                }
            }
        },

        placeHogwartsCard: function(card, zone, zoneElemId, playerId) {
            let elementId = 'hogwarts_card_' + card.id;
            if (playerId) {
                elementId += '_p' + playerId;
            }
            dojo.place(
              this.format_block( 'jstpl_hogwarts_card', {
                  elementId: elementId,
                  cardId: card.id,
                  posX: -100 * parseInt(card.type_arg),
                  posY: 100 * parseInt(card.type),
              }), zoneElemId);
            zone.placeInZone(elementId);
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
                effectIdsToRemove.forEach(this.removeActiveEffect);

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
            let iconX = 0;
            let iconY = 0;
            switch (effect.effect_trigger) {
                case 'onDefeatVillain':
                    iconX = 30;
                    iconY = 100;
                    break;
            }

            dojo.place(
              this.format_block('jstpl_active_effect', {
                  elementId: 'active_effect_' + effect.id,
                  effectId: effect.id,
                  effectName: effect.name,
                  iconX: iconX,
                  iconY: iconY,
              }), 'active_effects');
            this.visibleEffectIds.push(effect.id);
            this.addTooltipHtml('active_effect_' + effect.id, this.format_block('jstpl_hogwarts_card_tooltip', {
                cardName: effect.name
            }));
        },

        removeActiveEffect: function(effectId) {
            this.removeTooltip('active_effect_' + effectId);
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
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
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
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
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
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                    id : cardId,
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

            if (this.player_id == notif.args.player_id) {
                let cardElemId = 'hogwarts_card_' + notif.args.card_id + '_p' + notif.args.player_id;
                this.playerHand.removeFromZone(cardElemId);
                dojo.removeClass(dojo.byId(cardElemId), 'can_play');
                this.disconnect( $(cardElemId), 'onclick');
                this.playedCards.placeInZone(cardElemId);
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
        },
   });
});
