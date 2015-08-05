<?php

namespace Trivia;

use Trivia\Output\Output;

class Game
{
    /** @var Player[] */
    private $players;

    /** @var int */
    private $currentPlayerIndex = 0;

    /** @var Questions */
    private $questions;

    private $isGettingOutOfPenaltyBox;

    /** @var Output */
    private $output;

    function  __construct(Output $output)
    {
        $this->players = array();

        $this->prepareQuestions();

        $this->output = $output;

        $this->messages = new Messages($output);
    }

    function isPlayable()
    {
        return ($this->howManyPlayers() >= 2);
    }

    function add($playerName)
    {
        $player = new Player($playerName);
        $this->players[] = $player;

        $this->messages->newPlayer($player);
        $this->messages->numberOfPlayers($this->howManyPlayers());
    }

    function howManyPlayers()
    {
        return count($this->players);
    }

    function  roll($roll)
    {
        $this->messages->isPlaying($this->currentPlayer());
        $this->messages->rolls($roll);
        if ($this->currentPlayer()->isInPenaltyBox()) {
            $this->isGettingOutOfPenaltyBox = $roll % 2 != 0;

            if ($this->isGettingOutOfPenaltyBox) {
                $this->messages->isGettingOutOfPenalty($this->currentPlayer());
                $this->playTurn($roll);
            } else {
                $this->messages->isNotGettingOutOfPenalty($this->currentPlayer());
            }
        } else {
            $this->playTurn($roll);
        }
    }

    function  askQuestion()
    {
        $question = $this->questions->questionFor($this->currentPlayer()->position());
        $this->messages->question($question);

        return $question;
    }

    function currentCategory()
    {
        return $this->questions->categoryNameFor($this->currentPlayer()->position());
    }

    function wasCorrectlyAnswered()
    {
        if ($this->currentPlayer()->isInPenaltyBox()) {
            if ($this->isGettingOutOfPenaltyBox) {

                $this->winPurse();

                $winner = $this->didPlayerWin();
                $this->nextPlayer();

                return $winner;
            } else {
                $this->nextPlayer();
                return true;
            }
        } else {

            $this->winPurse();

            $winner = $this->didPlayerWin();
            $this->nextPlayer();

            return $winner;
        }
    }

    function wrongAnswer()
    {
        $this->echoln("Question was incorrectly answered");
        $this->echoln(
            $this->players[$this->currentPlayerIndex]->name() . " was sent to the penalty box"
        );
        $this->currentPlayer()->gotoPenaltyBox();

        $this->nextPlayer();
        return true;
    }

    function didPlayerWin()
    {
        $currentPurses = $this->currentPlayer()->purses();
        return !($currentPurses == 6);
    }

    function echoln($string)
    {
        $this->output->write($string . "\n");
    }

    /**
     * @return string
     */
    protected function currentPlayerName()
    {
        return $this->currentPlayer()->name();
    }

    /**
     * @return Player
     */
    protected function currentPlayer()
    {
        return $this->players[$this->currentPlayerIndex];
    }

    /**
     * @param $roll
     */
    protected function movePlayer($roll)
    {
        $nextPlace = ($this->currentPlayer()->position() + $roll) % 12;
        $this->currentPlayer()->moveTo($nextPlace);
    }

    protected function winPurse()
    {
        $this->currentPlayer()->winPurse();
        $this->messages->winPurse($this->currentPlayer());
    }

    /**
     * @param $roll
     */
    protected function playTurn($roll)
    {
        $this->movePlayer($roll);

        $this->echoln(
            $this->currentPlayerName()
            . "'s new location is "
            . $this->currentPlayer()->position()
        );
        $this->echoln("The category is " . $this->currentCategory());
        $this->askQuestion();
    }

    protected function prepareQuestions()
    {
        $this->questions = new Questions();
    }

    protected function nextPlayer()
    {
        $this->currentPlayerIndex = ($this->currentPlayerIndex + 1) % $this->howManyPlayers();
    }

}
