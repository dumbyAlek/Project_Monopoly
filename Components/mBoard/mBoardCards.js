// ===== CARD LOGIC JS =====
let pendingCardAction = null;


// ------- Community Chest Cards -------
export const communityChestCards = [
    { text: "DOCTOR'S FEE<br>PAY $50", action: "pay", amount: 50 },
    { text: "BANK ERROR IN YOUR FAVOR<br>COLLECT $200", action: "collect", amount: 200 },
    { text: "PAY SCHOOL FEES<br>PAY $150", action: "pay", amount: 150 },
    { text: "FROM SALE OF STOCK<br>YOU GET $45", action: "collect", amount: 45 },
    { text: "HOLIDAY FUND MATURES<br>RECEIVE 100 Taka", action: "collect", amount: 100 },
    { text: "INCOME TAX REFUND<br>COLLECT 20 Taka", action: "collect", amount: 20 },
    { text: "LIFE INSURANCE MATURES<br>COLLECT 100 Taka", action: "collect", amount: 100 },
    { text: "PAY HOSPITAL FEES<br>PAY $100", action: "pay", amount: 100 },
    { text: "GET OUT OF JAIL FREE<br>Keep this card", action: "jail_free", amount: 0 },
    { text: "YOU INHERIT $100<br>COLLECT $100", action: "collect", amount: 100 }
];

// ------- Chance Cards -------
export const chanceCards = [
    { text: "ADVANCE TO GO<br>COLLECT 200 Taka", action: "advance_go", amount: 200 },
    { text: "GO TO JAIL<br>Do not pass GO", action: "go_jail", amount: 0 },
    { text: "PAY POOR TAX<br>PAY 15 Taka", action: "pay", amount: 15 },
    { text: "TAKE A TRIP TO READING RAILROAD<br>If you pass GO collect 200 Taka", action: "move_railroad", amount: 0 },
    { text: "BANK PAYS YOU DIVIDEND<br>COLLECT 50 Taka", action: "collect", amount: 50 },
    { text: "GET OUT OF JAIL FREE<br>Keep this card", action: "jail_free", amount: 0 },
    { text: "GO BACK 3 SPACES<br>Move back 3 spaces", action: "go_back", amount: 3 },
    { text: "SPEEDING FINE<br>PAY 15 Taka", action: "pay", amount: 15 },
    { text: "ADVANCE TO BOARDWALK<br>Collect 200 if you pass GO", action: "advance_boardwalk", amount: 0 },
    { text: "YOU HAVE WON A CROSSWORD COMPETITION<br>COLLECT 100 Taka", action: "collect", amount: 100 }
];

// ------ Functions to display cards ------
export function showCommunityChest() {
    const randomCard = communityChestCards[Math.floor(Math.random() * communityChestCards.length)];
    displayCard('community-chest', 'Community Chest', randomCard);
}

export function showChance() {
    const randomCard = chanceCards[Math.floor(Math.random() * chanceCards.length)];
    displayCard('chance', 'Chance', randomCard);
}

function displayCard(type, title, cardData) {
    const modal   = document.getElementById('cardModal');
    const content = document.getElementById('cardContent');

    const iconHtml = (type === 'community-chest')
        ? '<div class="card-icon"><img src="../../Assets/chest.webp" alt="Chest"></div>'
        : '<div class="card-icon"><img src="../../Assets/ques.webp" alt="Chance"></div>';

    content.className = `card-modal ${type}`;
    content.innerHTML = `
        <div class="card-header">${title}</div>
        ${iconHtml}
        <div class="card-text">${cardData.text}</div>
        <button class="close-btn" onclick="closeCard()">OK</button>
    `;

    modal.classList.add('active');

    pendingCardAction = cardData;
}

export function closeCard() {
  document.getElementById('cardModal').classList.remove('active');

  if (pendingCardAction) {
    // fire and forget; or await if you want
    window.__applyPickedCard?.(pendingCardAction);
    pendingCardAction = null;
  }
}


function applyCardAction(cardData) {
    console.log('Card Action:', cardData.action, 'Amount:', cardData.amount);
    // Hook your game logic here
    switch(cardData.action) {
        case 'collect':
            break;
        case 'pay':
            break;
        case 'jail_free':
            break;
        case 'go_jail':
            break;
        case 'advance_go':
            break;
        case 'go_back':
            break;
        case 'move_railroad':
            break;
        case 'advance_boardwalk':
            break;
    }
}

// Close with Esc
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeCard();
});
