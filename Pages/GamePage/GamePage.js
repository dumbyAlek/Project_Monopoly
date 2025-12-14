// GamePage,js
console.log("GamePage loaded with mBoard included");

function updatePlayerMoney(playerIndex, amount) {
    const panel = document.querySelector(`.player-panel[data-player-id="${playerIndex}"]`);
    if (!panel) return;

    const moneyEl = panel.querySelector(".money-value");
    if (moneyEl) moneyEl.textContent = amount;
}


function attachPlayerEvents() {
    const panels = document.querySelectorAll(".player-panel");
    panels.forEach(p => {
        p.querySelector(".get-out-jail-btn").onclick = () => GameActionsProxy.getOutOfJail(p);
        p.querySelector(".pay-loan-btn").onclick = () => {
            const amount = parseInt(prompt("Enter loan amount:"));
            if (!isNaN(amount) && amount > 0) GameActionsProxy.payLoan(p, amount);
        }
        // p.querySelector(".pay-debt-btn").onclick = () => GameActionsProxy.payDebt(p);
        // p.querySelector(".ask-debt-btn").onclick = () => GameActionsProxy.askDebt(p);
    });
}

const saveBtn = document.getElementById("save-game-btn");
if (saveBtn) {
    saveBtn.onclick = () => GameFacade.saveGame(currentGameId);
}


// Initial attach
attachPlayerEvents();

// ========================================== Buying from Player ==============================================================

let tradeData = {};

function openTradeModal(buyer, owner, propertyId) {
    tradeData = {
        buyer_id: buyer.id,
        buyer_name: buyer.name,
        owner_id: owner.id,
        owner_name: owner.name,
        property_id: propertyId,
        offer: null
    };

    document.getElementById("buyerName").innerText = buyer.name;
    document.getElementById("ownerName").innerText = owner.name;

    document.getElementById("buyerView").style.display = "block";
    document.getElementById("ownerView").style.display = "none";
    document.getElementById("resultView").style.display = "none";

    document.getElementById("tradeModal").style.display = "block";
}

document.getElementById("offerAmount").addEventListener("input", function () {
    const value = parseInt(this.value);
    document.getElementById("proceedBtn").disabled = !(value > 0);
});

document.getElementById("proceedBtn").onclick = () => {
    tradeData.offer = document.getElementById("offerAmount").value;

    document.getElementById("buyerView").style.display = "none";
    document.getElementById("ownerView").style.display = "block";
    document.getElementById("offeredPrice").innerText = tradeData.offer;
};

document.getElementById("declineTradeBtn").onclick = () => {
    document.getElementById("ownerView").style.display = "none";
    document.getElementById("resultView").style.display = "block";
    document.getElementById("resultMessage").innerText =
        "❌ The owner declined your offer.";
};

document.getElementById("acceptTradeBtn").onclick = () => {
    fetch("../../Backend/GameActions/buyPropertyFromPlayer.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(tradeData)
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("ownerView").style.display = "none";
        document.getElementById("resultView").style.display = "block";

        if (data.success) {
            document.getElementById("resultMessage").innerText =
                "🤝 Deal Done! Property transferred successfully.";
                refreshSidebars();
        } else {
            document.getElementById("resultMessage").innerText =
                "❌ " + data.message;
        }
    });
};

function closeTradeModal() {
    document.getElementById("tradeModal").style.display = "none";
}

// ========================================== Selling to Player ==============================================================

let sellTradeData = {};

function openSellTradeModal(owner, buyer, propertyId) {
    sellTradeData = {
        owner_id: owner.id,
        owner_name: owner.name,
        buyer_id: buyer.id,
        buyer_name: buyer.name,
        property_id: propertyId,
        price: null
    };

    document.getElementById("sellerName").innerText = owner.name;
    document.getElementById("buyerConfirmName").innerText = buyer.name;

    document.getElementById("sellerView").style.display = "block";
    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "none";

    document.getElementById("sellTradeModal").style.display = "block";
}

document.getElementById("askingPrice").addEventListener("input", function () {
    document.getElementById("proceedToBuyerBtn").disabled = !(this.value > 0);
});

document.getElementById("proceedToBuyerBtn").onclick = () => {
    sellTradeData.price = document.getElementById("askingPrice").value;

    document.getElementById("finalPrice").innerText = sellTradeData.price;
    document.getElementById("sellerView").style.display = "none";
    document.getElementById("buyerConfirmView").style.display = "block";
};

document.getElementById("declineSellBtn").onclick = () => {
    document.getElementById("buyerConfirmView").style.display = "none";
    document.getElementById("sellResultView").style.display = "block";
    document.getElementById("sellResultMessage").innerText =
        "❌ Buyer declined the offer.";
};

document.getElementById("acceptSellBtn").onclick = () => {
    fetch("../../Backend/GameActions/sellPropertyToPlayer.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(sellTradeData)
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("buyerConfirmView").style.display = "none";
        document.getElementById("sellResultView").style.display = "block";

        if (data.success) {
            document.getElementById("sellResultMessage").innerText =
                "🤝 Deal completed successfully.";
            refreshSidebars();
        } else {
            document.getElementById("sellResultMessage").innerText =
                "❌ " + data.message;
        }
    });
};

function closeSellTradeModal() {
    document.getElementById("sellTradeModal").style.display = "none";
}

function refreshSidebars() {
    fetch(`../../Backend/GameActions/getSidebarData.php?game_id=${currentGameId}`)
        .then(res => res.json())
        .then(data => {
            // Update bank
            document.querySelector(".bank-money").innerText =
                `Bank Money: $${data.bank.total_funds}`;

            // Update players
            data.players.forEach(p => {
                const panel = document.querySelector(
                    `.player-panel[data-player-id="${p.player_id}"]`
                );
                if (!panel) return;

                panel.querySelector(".money-value").innerText = p.money;
                panel.querySelector("p:nth-child(2)").innerHTML =
                    `Properties: ${p.number_of_properties} ($${p.propertyWorthCash})`;
                panel.querySelector("p:nth-child(4)").innerHTML =
                    `Loan: $0 <button class="pay-loan-btn">Pay</button>`;
                panel.querySelector("p:nth-child(5)").innerText =
                    `Debt from Players: $${p.debt_from_players}`;
            });

            // Re-attach button events
            attachPlayerEvents();
        });
}
