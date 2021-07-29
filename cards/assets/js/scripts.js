class CardGame {

    constructor() {
        this.attempts = 0;
        this.points = 0;
        this.cardCount = 0;
        this.cardNumbers = [1, 2, 3, 4];
        this.cards = ['card1', 'card2', 'card3', 'card4'];
        this.currentDropArea;
        this.attemptsEl = document.getElementById('attempts');
        this.pointsEl = document.getElementById('points');
        this.resultScoreEl = document.getElementById('result');
        this.dropAreaEl = document.getElementsByClassName('drop-area')[0];
        this.cardsEl = document.getElementById('cards');
        this.resetGame();
    }

    allowDrop(ev) {
        ev.preventDefault();
    }

    onDragCard(ev) {
        ev.dataTransfer.setData("text", ev.target.id);
    }

    onDropCard(ev) {
        ev.preventDefault();
        this.currentDropArea = ev.target;
        if ('drop-area' != this.currentDropArea.className)
            this.currentDropArea = this.dropAreaEl;
        let number = ev.dataTransfer.getData("text");
        this.dropCardInDropArea(number);
        this.checkScore(number)
    }

    dropCardInDropArea(number) {
        document.getElementById(number).remove();
        let newCard = document.createElement('div');
        newCard.classList.add("new-card");
        newCard.innerHTML = `<span>${number}</span>`;
        this.currentDropArea.innerHTML = '';
        this.currentDropArea.appendChild(newCard);

    }

    resetGame() {
        this.attempts = 0;
        this.points = 0;
        this.cardCount = 0;
        this.attemptsEl.innerHTML = 0;
        this.pointsEl.innerHTML = 0;
        this.resultScoreEl.innerHTML = '';
        this.dropAreaEl.innerHTML = '';
        this.cardsEl.innerHTML = `
                <img id="1" class="card1" draggable="true" ondragstart="cardGame.onDragCard(event)" src="assets/images/card.png">
                <img id="2" class="card2" draggable="true" ondragstart="cardGame.onDragCard(event)" src="assets/images/card.png">
                <img id="3" class="card3" draggable="true" ondragstart="cardGame.onDragCard(event)" src="assets/images/card.png">
                <img id="4" class="card4" draggable="true" ondragstart="cardGame.onDragCard(event)" src="assets/images/card.png">`;
        this.shuffleCards();


    }

    shuffleCards() {
        this.cardNumbers.sort((a, b) => 0.5 - Math.random());
        for (let card in this.cards) {
            document.getElementsByClassName(this.cards[card])[0].setAttribute('id', this.cardNumbers[card]);
        }

    }

    checkScore(number) {
        this.cardCount++;
        if (number == this.cardCount) {
            this.points += 10;
            this.resultScoreEl.innerHTML = '<span class="correct">Correct</span>';

        } else {
            this.points -= 5;
            this.resultScoreEl.innerHTML = '<span class="incorrect">Incorrect</span>';
        }
        this.attemptsEl.innerHTML = this.cardCount;
        this.pointsEl.innerHTML = this.points;
    }

}

const cardGame = new CardGame();


