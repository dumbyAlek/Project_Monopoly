  import { animateDice, diceFaces, getRandomPosition, mappingLabels , generateTiles} from './mBoardVisuals.js';
  import { tiles } from './mBoardVisuals.js';
  import { TileDecorator } from './TileDecorator.js';
  import { GameActionsProxy } from '../../Pages/GamePage/GameActionsProxy.js';

  const rollBtn = document.getElementById("rollBtn");
  const endTurnBtn = document.getElementById("endTurnBtn");

  const gameProperties = window.gameProperties || {};

  function setTurnPhase(phase) {
    if (phase === "roll") {
      rollBtn.classList.remove("hidden", "transparent");
      endTurnBtn.classList.add("hidden");
    }

    if (phase === "end") {
      rollBtn.classList.add("transparent");
      endTurnBtn.classList.remove("hidden");
    }
  }

  window.mappingLabels = mappingLabels;

  const diceResult = document.getElementById("diceResult");

  for (let i = 0; i < tiles.length; i++) {
    const meta = gameProperties?.[i];
    if (meta) {
      tiles[i].id = meta.property_id;
      tiles[i].price = meta.price;
      tiles[i].rent = meta.rent;
      tiles[i].owner_id = meta.owner_id;
    }
  }

  generateTiles();
  renderBuildingsFromDB();  
  const PLAYER_ICONS = ["🏰", "🚗", "🛵", "🎩"];

  const players = (window.playersData || []).map((p, index) => ({
    id: p.player_id,
    pos: Number(p.position ?? 0),
    icon: PLAYER_ICONS[index % PLAYER_ICONS.length],
    element: null
  }));


  console.log("Loaded playersData:", window.playersData);
  console.log("Initial board positions:", players.map(x => ({ id: x.id, pos: x.pos })));

  if (players.length === 0) {
    console.error("No playersData found. window.playersData is empty.");
  }


  let currentPlayerIndex = 0;
  let turnLocked = false;  // prevents advancing turn until actions are done
  let tileResolved = false; //  true before any actions/end-turn allowed

  function anyModalOpen() {
    const sellOpen = document.getElementById("sellTradeModal")?.style.display === "block";
    const tradeOpen = document.getElementById("tradeModal")?.style.display === "block";
    const cardOpen = document.getElementById("cardModal")?.classList?.contains("active"); // if you use this
    return sellOpen || tradeOpen || cardOpen;
  }

  function canPlayerInteract() {
    return turnLocked && tileResolved && !anyModalOpen();
  }

  async function apiPayRentAuto({ payerId, propertyId, tileIndex }) {
    const res = await fetch("../../Backend/GameActions/payRent.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        gameId: Number(window.currentGameId),
        payerId: Number(payerId),
        propertyId: Number(propertyId),
        tileIndex: Number(tileIndex)
      })
    });
    return res.json();
  }

  async function apiPayBankAuto({ playerId, tileIndex }) {
    const res = await fetch("../../Backend/GameActions/payBank.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        gameId: Number(window.currentGameId),
        playerId: Number(playerId),
        type: "tax",
        tileIndex: Number(tileIndex)
      })
    });
    return res.json();
  }

  async function apiCollectBank({ playerId, amount, reason }) {
    const res = await fetch("../../Backend/GameActions/collectBank.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        gameId: Number(window.currentGameId),
        playerId: Number(playerId),
        amount: Number(amount),
        reason: String(reason || "")
      })
    });
    return res.json();
  }

  function waitUntil(fn) {
    return new Promise(resolve => {
      const t = setInterval(() => {
        if (fn()) { clearInterval(t); resolve(); }
      }, 100);
    });
  }

  async function forcePickCard(type, playerObj) {
    alert(`GOT ${type === "chance" ? "Chance" : "Community Chest"}! Click OK then pick.`);

    window.__applyPickedCard = async (cardData) => {
      await applyPickedCardAction(cardData, playerObj);
    };

    const cards = await import("./mBoardCards.js");
    if (type === "chance") cards.showChance();
    else cards.showCommunityChest();

    await waitUntil(() => !document.getElementById("cardModal")?.classList.contains("active"));
  }

  async function applyPickedCardAction(cardData, playerObj) {
    switch(cardData.action) {
      case "collect": {
        const j = await apiCollectBank({ playerId: playerObj.id, amount: cardData.amount, reason: "Card collect" });
        if (!j.success) return alert(j.message || "Collect failed");
        alert(`Collected ${j.amount} Taka`);
        if (typeof updatePlayerMoney === "function") updatePlayerMoney(playerObj.id, j.newBalance);
        break;
      }

      case "pay": {
        // card pays are not "auto-calc", so still send amount
        const res = await fetch("../../Backend/GameActions/payBank.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            gameId: Number(window.currentGameId),
            playerId: Number(playerObj.id),
            type: "card",
            amount: Number(cardData.amount),
            reason: "Card pay"
          })
        });
        const j = await res.json();
        if (!j.success) return alert(j.message || "Payment failed");
        alert(`Paid ${j.amount} Taka`);
        if (typeof updatePlayerMoney === "function") updatePlayerMoney(playerObj.id, j.newBalance);
        break;
      }

      case "jail_free":
        await fetch("../../Backend/GameActions/grantGetOutCard.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ gameId: Number(window.currentGameId), playerId: Number(playerObj.id) })
        });
        alert("Received Get Out of Jail Free card!");
        break;

      case "go_jail":
        await fetch("../../Backend/GameActions/goToJail.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ gameId: Number(window.currentGameId), playerId: Number(playerObj.id) })
        });
        const jailIndex = tiles.findIndex(t => t.category === "jail");
        if (jailIndex !== -1) { playerObj.pos = jailIndex; movePlayer(playerObj, mappingLabels); }
        alert("Go to Jail!");
        break;

      default:
        console.log("Unimplemented card action:", cardData);
        break;
    }
  }


  async function loadCurrentTurnFromServer() {
    try {
      const url = `../../Backend/turnState.php?game_id=${window.currentGameId}`;
      const res = await fetch(url);

      const raw = await res.text(); // <-- read as text first
      console.log("turnState status:", res.status, "content-type:", res.headers.get("content-type"));
      console.log("turnState raw response (first 200):", raw.slice(0, 200));

      // now try json parse
      const data = JSON.parse(raw);
      return data?.currentPlayerId ?? null;
    } catch (e) {
      console.error("Failed to load turn state:", e);
      return null;
    }
  }

  function saveCurrentTurnToServer(playerId) {
    // use fetch (or sendBeacon if you want later)
    fetch(`../../Backend/turnState.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        gameId: Number(window.currentGameId),
        currentPlayerId: Number(playerId)
      })
    }).catch(console.error);
  }


  // Move a player to its tile
  export function movePlayer(playerObj, mappingLabels) {
    const tile = document.getElementById(mappingLabels[playerObj.pos]);
    if (!tile) return;
    const placeholder = tile.querySelector(".player-placeholder");
    if (!placeholder.contains(playerObj.element)) {
      placeholder.appendChild(playerObj.element);
    }
  }

  // Initialize player pieces
  export function initPlayers(mappingLabels) {
    players.forEach(p => {
      const piece = document.createElement("div");
      piece.classList.add("player", `p${p.id}`);
      piece.textContent = p.icon;
      p.element = piece;
      movePlayer(p, mappingLabels);
    });
  }

  // Roll dice and update player position
  export async function rollDice(mappingLabels) {
    if (turnLocked) return; // block rolling if turn is in progress

    try {
      const res = await fetch("../../Backend/mBoard/rollDice.php");
      const data = await res.json();
      const die1 = data.die1;
      const die2 = data.die2;
      const total = die1 + die2;

      const p = players[currentPlayerIndex];

      // fetch jail state for p.id (endpoint: getPlayerState.php) OR reuse window.playersData if it includes is_in_jail
      const playerRow = window.playersData?.find(x => Number(x.player_id) === Number(p.id));
      if (playerRow?.is_in_jail) {
        alert("You are in Jail. Use card or pay fine to roll.");
        return;
      }

      animateDice(document.getElementById("dice1"), diceFaces[die1-1]);
      animateDice(document.getElementById("dice2"), diceFaces[die2-1]);

      const oldPos = p.pos;
      const rawPos = oldPos + total;
      const passedGo = rawPos >= 40;     // true if crossing/landing on GO
      p.pos = rawPos % 40;

      movePlayer(p, mappingLabels);
      enableTileActions(p.pos);

      tileResolved = false;
      enableTileActions(-1);

      await resolveLandingTile(p);

      tileResolved = true;
      enableTileActions(p.pos);


      // ✅ Only do GO payout if player actually passed GO
      if (passedGo) {
        const r = await fetch("../../Backend/GameActions/passGo.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            gameId: Number(window.currentGameId),
            playerId: Number(p.id),
          })
        });

        const j = await r.json();

        if (j.success) {
          const playerName =
            window.playersData?.find(pp => Number(pp.player_id) === Number(p.id))?.player_name
            || `Player ${p.id}`;

          alert(`${playerName} passed GO, collected $${j.amount}`);

          if (typeof updatePlayerMoney === "function") {
            updatePlayerMoney(p.id, j.newBalance);
          }
          await checkGameOver();
        } else {
          console.error("passGo failed:", j.message);
        }
      }


      fetch("../../Backend/GameActions/updatePlayerPosition.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ playerId: p.id, position: p.pos, gameId: Number(window.currentGameId) })
      }).catch(console.error);

      diceResult.textContent = `Player ${p.id} rolled ${die1} + ${die2} = ${total}`;

      // Lock the turn until player finishes actions
      turnLocked = true;
      setTurnPhase("end");


    } catch (err) { console.error("Dice error:", err); }
  }

  async function resolveLandingTile(playerObj) {
    const idx = Number(playerObj.pos);
    const tile = tiles[idx];
    const meta = window.gameProperties?.[idx]; // db-mapped meta (owner_id, price, rent, etc.)

    // 1) Go to Jail tile
    if (tile.category === "goToJail") {
      await fetch("../../Backend/GameActions/goToJail.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          gameId: Number(window.currentGameId),
          playerId: Number(playerObj.id)
        })
      }).catch(console.error);

      // force UI to jail position
      const jailIndex = tiles.findIndex(t => t.category === "jail");
      if (jailIndex !== -1) {
        playerObj.pos = jailIndex;
        movePlayer(playerObj, mappingLabels);
      }
      alert("GO TO JAIL!");
      return;
    }

    // 2) If player is currently in jail, don’t allow normal play (optional strict rule)
    if (tile.category === "jail") {
      return; // just visiting unless is_in_jail is true (handled elsewhere)
    }

    // 3) Chance / Community: show “Click to pick” and wait
    if (tile.category === "chance") {
      await forcePickCard("chance", playerObj);
      return;
    }

    if (tile.category === "community") {
      await forcePickCard("community", playerObj);
      return;
    }

    // 4) Tax tiles auto-pay (tile 4 income tax, tile 38 luxury tax)
    if (tile.category === "tax") {
      const j = await apiPayBankAuto({ playerId: playerObj.id, tileIndex: idx });
      if (!j.success) return alert(j.message || "Tax payment failed");

      alert(`Paid TAX: ${j.amount} Taka`);
      if (typeof updatePlayerMoney === "function") updatePlayerMoney(playerObj.id, j.newBalance);
      return;
    }


    // 5) Utility tiles auto-pay (if owned) OR do nothing (if unowned)
    if (tile.category === "utility") {
      if (!meta?.owner_id) return;
      if (Number(meta.owner_id) === Number(playerObj.id)) return;

      const propertyId = Number(meta.property_id ?? tiles[idx].property_id ?? meta.id ?? 0);

      if (!propertyId) { console.error("Missing propertyId in window.gameProperties for tile", idx, meta); return; }

      const j = await apiPayRentAuto({ payerId: playerObj.id, propertyId, tileIndex: idx });
      if (!j.success) return alert(j.message || "Rent payment failed");

      alert(`Paid utility rent: ${j.amount} Taka to Player ${j.receiverId}`);
      if (typeof updatePlayerMoney === "function") {
        updatePlayerMoney(playerObj.id, j.payerNewBalance);
        updatePlayerMoney(j.receiverId, j.receiverNewBalance);
      }
      return;
    }


    // 6) Property / Station auto-rent if owned by someone else
    if (tile.category === "property" || tile.category === "station") {
      if (!meta?.owner_id) return;
      if (Number(meta.owner_id) === Number(playerObj.id)) return;

      const propertyId = Number(meta.property_id ?? tiles[idx].property_id ?? meta.id ?? 0);

      if (!propertyId) { console.error("Missing propertyId in window.gameProperties for tile", idx, meta); return; }

      const j = await apiPayRentAuto({ payerId: playerObj.id, propertyId, tileIndex: idx });
      if (!j.success) return alert(j.message || "Rent payment failed");

      alert(`Paid rent: ${j.amount} Taka to Player ${j.receiverId}`);
      if (typeof updatePlayerMoney === "function") {
        updatePlayerMoney(playerObj.id, j.payerNewBalance);
        updatePlayerMoney(j.receiverId, j.receiverNewBalance);
      }
      return;
    }


    // 7) others: GO / FREE / etc. do nothing
  }


  if (rollBtn) {
    rollBtn.addEventListener("click", () => rollDice(mappingLabels));
  }

  (async () => {
    // Load current player from Log
    const currentPlayerId = await loadCurrentTurnFromServer();

    if (currentPlayerId != null) {
      const idx = players.findIndex(p => Number(p.id) === Number(currentPlayerId));
      if (idx !== -1) currentPlayerIndex = idx;
    }

    initPlayers(mappingLabels);
    setTurnPhase("roll");
  })();


  // Add button event listeners for all tiles
  tiles.forEach((t, i) => {
      const tileEl = document.getElementById(mappingLabels[i]);
      if (!tileEl) return;

      const buyBtn = tileEl.querySelector(".buy-btn");
      const sellBtn = tileEl.querySelector(".sell-btn");
      const placeHouseOrHotelBtn = tileEl.querySelector(".placeHouseOrHotel-btn");

      if (buyBtn) buyBtn.addEventListener("click", async () => {
        if (!canPlayerInteract()) return;
        const player = players[currentPlayerIndex];
        const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);
        if (!playerPanel) return console.error("Missing player panel for", player.id);

        // GameActionsProxy.buyProperty(playerPanel, dbPropertyId);
        await GameActionsProxy.buyProperty(playerPanel, i);

        enableTileActions(i);
        setTurnPhase("end");
      });

      if (sellBtn) sellBtn.addEventListener("click", async () => {
        if (!canPlayerInteract()) return;
        const player = players[currentPlayerIndex];
        const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);
        if (!playerPanel) return console.error("Missing player panel for", player.id);

        // GameActionsProxy.sellProperty(playerPanel, dbPropertyId);
        await GameActionsProxy.sellProperty(playerPanel, i);
        
        enableTileActions(i);
        setTurnPhase("end");
      });

      if (placeHouseOrHotelBtn) placeHouseOrHotelBtn.addEventListener("click", async () => {
        if (!canPlayerInteract()) return;


        const player = players[currentPlayerIndex];
        const playerPanel = document.querySelector(
          `.player-panel[data-player-id="${player.id}"]`
        );
        if (!playerPanel) return;

        const meta = window.gameProperties?.[i];
        if (!meta) return;

        const houseCount = Number(meta.house_count ?? 0);
        const hasHotel   = Number(meta.hotel_count ?? 0);

        const result = await GameActionsProxy.placeHouseOrHotel(
          playerPanel,
          i,
          houseCount,
          hasHotel
        );

        if (!result || !result.success) {
          alert(result?.message || "Failed to place house/hotel");
          return;
        }

        meta.house_count = result.house_count;
        meta.hotel_count = result.hotel_count;
        if (window.tiles?.[i]) {
          window.tiles[i].house_count = result.house_count;
          window.tiles[i].hotel_count = result.hotel_count;
        }


        updateHouses(i, result.house_count, result.hotel_count > 0);

        enableTileActions(i);
        setTurnPhase("end");
      });

  });

  export function updateHouses(tileIndex, houseCount, hasHotel = false) {
    const tile = document.getElementById(mappingLabels[tileIndex]);
    if (!tile) return;

    const container = tile.querySelector(".houses-container");
    container.innerHTML = ""; // clear previous houses/hotel

    // Use TileDecorator's static methods 
    if (hasHotel) {
      TileDecorator.addHotel(tile);
    } else if (houseCount > 0) {
      TileDecorator.addHouses(tile, houseCount);
    }
  }

  function enableTileActions(tileIndex) {
    tiles.forEach((_, i) => {
      const tileEl = document.getElementById(mappingLabels[i]);
      if (!tileEl) return;

      const buyBtn = tileEl.querySelector(".buy-btn");
      const sellBtn = tileEl.querySelector(".sell-btn");

      if (buyBtn) buyBtn.disabled = i !== tileIndex;
      if (sellBtn) sellBtn.disabled = i !== tileIndex;
    });
  }

  if (endTurnBtn) {
      endTurnBtn.addEventListener("click", () => {
        if (!canPlayerInteract()) return;
      turnLocked = false;

      // Advance to next player
      currentPlayerIndex = (currentPlayerIndex + 1) % players.length;

      // ✅ Persist whose turn is next
      saveCurrentTurnToServer(players[currentPlayerIndex].id);

      enableTileActions(-1);
      setTurnPhase("roll");
    });
  }

  function renderBuildingsFromDB() {
    for (let i = 0; i < tiles.length; i++) {
      const meta = window.gameProperties?.[i];
      if (!meta) continue;

      const houseCount = Number(meta.house_count ?? 0);
      const hasHotel   = Number(meta.hotel_count ?? 0) > 0;

      updateHouses(i, houseCount, hasHotel);
    }
  }

  async function checkGameOver() {
    try {
      const res = await fetch("../../Backend/GameActions/checkGameOver.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ gameId: Number(window.currentGameId) })
      });

      const data = await res.json();

      if (!data.success) return false;
      if (!data.gameOver) return false;

      showGameOverScreen(data);
      return true;
    } catch (err) {
      console.error("checkGameOver failed:", err);
      return false;
    }
  }

  function showGameOverScreen(data) {
    rollBtn.disabled = true;
    endTurnBtn.disabled = true;

    const rankingText = data.ranking
      .map(r => `${r.rank}. ${r.player_name} - $${r.assets} ${r.isLoser ? "(Loser)" : ""}`)
      .join("\n");

    alert(`GAME OVER!\n\n${rankingText}`);
  }


  window.__mPlayers = players;
  window.tiles = tiles;