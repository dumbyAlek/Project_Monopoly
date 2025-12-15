// mBoard.js
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
    tiles[i].id = meta.id;         // optional, but useful
    tiles[i].price = meta.price;
    tiles[i].rent = meta.rent;
    tiles[i].owner_id = meta.owner_id;
  }
}

generateTiles();

const players = (window.playersData || []).map(p => ({
  id: p.player_id,
  pos: Number(p.position ?? 0),
  element: null
}));

console.log("Loaded playersData:", window.playersData);
console.log("Initial board positions:", players.map(x => ({ id: x.id, pos: x.pos })));

if (players.length === 0) {
  console.error("No playersData found. window.playersData is empty.");
}


let currentPlayerIndex = 0;
let turnLocked = false;  // prevents advancing turn until actions are done

async function loadCurrentTurnFromServer() {
  try {
    const res = await fetch(`../../Backend/turnState.php?game_id=${window.currentGameId}`);
    const data = await res.json();
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
    piece.textContent = p.id;
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

    animateDice(document.getElementById("dice1"), diceFaces[die1-1]);
    animateDice(document.getElementById("dice2"), diceFaces[die2-1]);

    p.pos = (p.pos + total) % 40;

    if (tiles[p.pos].category === "goToJail") {
      const jailIndex = tiles.findIndex(t => t.category === "jail");
      if (jailIndex !== -1) p.pos = jailIndex;
    }

    movePlayer(p, mappingLabels);
    enableTileActions(p.pos);

    // ✅ Persist position right after moving
    fetch("../../Backend/GameActions/updatePlayerPosition.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ playerId: p.id, position: p.pos })
    }).catch(console.error);

    diceResult.textContent = `Player ${p.id} rolled ${die1} + ${die2} = ${total}`;

    // Lock the turn until player finishes actions
    turnLocked = true;
    setTurnPhase("end");


  } catch (err) { console.error("Dice error:", err); }
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

    if (buyBtn) buyBtn.addEventListener("click", async () => {
      if (!turnLocked) return;
      const player = players[currentPlayerIndex];
      const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);
      if (!playerPanel) return console.error("Missing player panel for", player.id);

      // GameActionsProxy.buyProperty(playerPanel, dbPropertyId);
      await GameActionsProxy.buyProperty(playerPanel, i);

      enableTileActions(i);
      setTurnPhase("end");
    });

    if (sellBtn) sellBtn.addEventListener("click", async () => {
      if (!turnLocked) return;
      const player = players[currentPlayerIndex];
      const playerPanel = document.querySelector(`.player-panel[data-player-id="${player.id}"]`);
      if (!playerPanel) return console.error("Missing player panel for", player.id);

      // GameActionsProxy.sellProperty(playerPanel, dbPropertyId);
      await GameActionsProxy.sellProperty(playerPanel, i);
      
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
    if (!turnLocked) return;

    turnLocked = false;

    // Advance to next player
    currentPlayerIndex = (currentPlayerIndex + 1) % players.length;

    // ✅ Persist whose turn is next
    saveCurrentTurnToServer(players[currentPlayerIndex].id);

    enableTileActions(-1);
    setTurnPhase("roll");
  });
}

window.__mPlayers = players;
window.tiles = tiles;