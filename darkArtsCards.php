<?php

class DarkArtsCard {
    public $id;
    public $gameNr;
    public $cardNr;
    public $name;
    public $onPlay;
    public $effect;
    public $desc;

    public function __construct($gameNr, $cardNr, $name, $onPlay, $effect, $desc) {
        $this->id = DarkArtsCards::cardId($gameNr, $cardNr);
        $this->gameNr = $gameNr;
        $this->cardNr = $cardNr;
        $this->name = $name;
        $this->onPlay = $onPlay;
        $this->effect = $effect;
        $this->desc = $desc;
    }
}

class DarkArtsCards {

    public $darkArtsCards;

    function __construct() {
        $this->darkArtsCards = array(
            0 => new DarkArtsCard(0, 0, clienttranslate('Petrification'), '1dmg_all', 'no_draw_cards',
                clienttranslate('ALL Heroes lose 1 ${health_icon} and cannot draw extra cards this turn')),

            1 => new DarkArtsCard(0, 1, clienttranslate('Expulso'), '2dmg', null,
                clienttranslate('Active Hero loses 2 ${health_icon}')),

            2 => new DarkArtsCard(0, 2, clienttranslate('He Who Must Not Be Named'), '+1loc_token', null,
                clienttranslate('Add 1 ${location_token} to the Location')),

            3 => new DarkArtsCard(0, 3, clienttranslate('Flipendo'), '1dmg_1discard', null,
                clienttranslate('Active Hero loses 1 ${health_icon} and discards a card')),
        );
    }

    public function gameCards($gameNr) {
        $cards = array();
        if ($gameNr >= 1) {
            $cards[] = $this->asCard($this->darkArtsCards[0], 2);
            $cards[] = $this->asCard($this->darkArtsCards[1], 3);
            $cards[] = $this->asCard($this->darkArtsCards[2], 3);
            $cards[] = $this->asCard($this->darkArtsCards[3], 2);
        }
        return $cards;
    }

    public function getDarkArtsCard($gameNr, $cardNr) {
        return $this->darkArtsCards[self::cardId($gameNr, $cardNr)];
    }

    public static function cardId($gameNr, $cardNr) {
        return (int)$gameNr * 100 + (int)$cardNr;
    }

    public function asCard($card, $x = 1) {
        return array ('type' => $card->gameNr,'type_arg' => $card->cardNr,'nbr' => $x );
    }


}