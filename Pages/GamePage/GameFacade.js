// Facade for internal data access

const GameFacade = (() => {
    // Returns Bank Money
    function getBankStatus() {
        const bankMoney = document.querySelector(".bank-money").textContent.match(/\$(\d+)/)[1];
        return { totalFunds: parseInt(bankMoney) };
    }

    function getPlayersStatus() {
        const panels = document.querySelectorAll(".player-panel");
        const players = [];
        panels.forEach(p => {
            players.push({
                playerId: p.dataset.playerId,
                money: parseInt(p.querySelector("p:nth-child(2)").textContent.match(/\$(\d+)/)[1]),
                propertiesCount: parseInt(p.querySelector("p:nth-child(3)").textContent),
                propertiesWorth: parseInt(p.querySelector("p:nth-child(3)").textContent.match(/\$(\d+)/)[1]),
                inJail: !p.querySelector(".get-out-jail-btn").disabled,
                hasGetOutCard: p.querySelector("p:nth-child(4)").textContent.includes("Yes"),
                debtToPlayers: parseInt(p.querySelector("p:nth-child(6)").textContent.match(/\$(\d+)/)[1]),
                debtFromPlayers: parseInt(p.querySelector("p:nth-child(5)").textContent.match(/\$(\d+)/)[1]),
            });
        });
        return players;
    }

    async function saveGame(gameId) {
        const bankStatus = getBankStatus();
        const playersStatus = getPlayersStatus();

        try {
            const response = await fetch('/../../Backend/saveGame.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    gameId: gameId,
                    bank: bankStatus,
                    players: playersStatus
                })
            });

            const result = await response.json();
            if(result.success) {
                alert("Game saved successfully!");
            } else {
                alert("Failed to save game.");
            }
        } catch (err) {
            console.error("Save Game Error:", err);
            alert("An error occurred while saving the game.");
        }
    }

    return { getBankStatus, getPlayersStatus, saveGame };
})();
