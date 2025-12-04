// Placeholder for future logic
console.log("GamePage loaded with mBoard included");

// Example: update player money
function updatePlayerMoney(playerIndex, amount) {
    const players = document.querySelectorAll(".player-info p");
    if (players[playerIndex]) {
        players[playerIndex].textContent = `Player ${playerIndex+1}: $${amount}`;
    }
}
