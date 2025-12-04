// mBoard.js 
const board = document.querySelector(".board");
const rollBtn = document.getElementById("rollBtn");
const diceResult = document.getElementById("diceResult");

// 1. Tiles (40 total)
const tiles = [
  { name: "GO", color: "#ffffff" }, { name: "Mediterranean Ave", color: "#8B4513" },
  { name: "Community Chest", color: "#ffffff" }, { name: "Baltic Ave", color: "#8B4513" },
  { name: "Income Tax", color: "#ffffff" }, { name: "Reading Railroad", color: "#000000" },
  { name: "Oriental Ave", color: "#ADD8E6" }, { name: "Chance", color: "#ffffff" },
  { name: "Vermont Ave", color: "#ADD8E6" }, { name: "Connecticut Ave", color: "#ADD8E6" },
  { name: "Jail / Just Visiting", color: "#ffffff" }, { name: "St. Charles Place", color: "#FF00FF" },
  { name: "Electric Company", color: "#ffffff" }, { name: "States Ave", color: "#FF00FF" },
  { name: "Virginia Ave", color: "#FF00FF" }, { name: "Penn. Railroad", color: "#000000" },
  { name: "St. James Place", color: "#FFA500" }, { name: "Community Chest", color: "#ffffff" },
  { name: "Tennessee Ave", color: "#FFA500" }, { name: "New York Ave", color: "#FFA500" },
  { name: "Free Parking", color: "#ffffff" }, { name: "Kentucky Ave", color: "#FF0000" },
  { name: "Chance", color: "#ffffff" }, { name: "Indiana Ave", color: "#FF0000" },
  { name: "Illinois Ave", color: "#FF0000" }, { name: "B. & O. Railroad", color: "#000000" },
  { name: "Atlantic Ave", color: "#FFFF00" }, { name: "Ventnor Ave", color: "#FFFF00" },
  { name: "Water Works", color: "#ffffff" }, { name: "Marvin Gardens", color: "#FFFF00" },
  { name: "Go To Jail", color: "#ffffff" }, { name: "Pacific Ave", color: "#008000" },
  { name: "North Carolina Ave", color: "#008000" }, { name: "Community Chest", color: "#ffffff" },
  { name: "Pennsylvania Ave", color: "#008000" }, { name: "Short Line", color: "#000000" },
  { name: "Chance", color: "#ffffff" }, { name: "Park Place", color: "#0000FF" },
  { name: "Luxury Tax", color: "#ffffff" }, { name: "Boardwalk", color: "#0000FF" }
];

// 2. Mapping IDs to match CSS grid
const mappingLabels = [
  "tile-C1","tile-L1","tile-L2","tile-L3","tile-L4","tile-L5","tile-L6","tile-L7","tile-L8","tile-L9","tile-C2",
  "tile-L10","tile-L11","tile-L12","tile-L13","tile-L14","tile-L15","tile-L16","tile-L17","tile-L18","tile-C3",
  "tile-L19","tile-L20","tile-L21","tile-L22","tile-L23","tile-L24","tile-L25","tile-L26","tile-L27","tile-C4",
  "tile-L28","tile-L29","tile-L30","tile-L31","tile-L32","tile-L33","tile-L34","tile-L35","tile-L36"
];

function generateTiles() { 
  tiles.forEach((t, i) => { 
    const tile = document.createElement("div"); 
    tile.classList.add("tile");
    tile.id = mappingLabels[i]; 
    tile.innerHTML = `
    <div class="tile-color" style="background:${t.color}"></div> 
    <div class="tile-name">${t.name}</div> 
    <div class="icon"></div> <div class="player-placeholder"></div> 
    `; 
    board.appendChild(tile); }); 
  } 

generateTiles();

// 4. Players
const players = [
  { id: 1, pos: 0, element: null },
  { id: 2, pos: 0, element: null },
  { id: 3, pos: 0, element: null },
  { id: 4, pos: 0, element: null }
];
let currentPlayerIndex = 0;

// 5. Initialize players
function initPlayers() {
  players.forEach(p => {
    const piece = document.createElement("div");
    piece.classList.add("player", `p${p.id}`);
    piece.textContent = p.id;
    p.element = piece;
    movePlayer(p);
  });
}

// 6. Move player
function movePlayer(playerObj) {
  const tile = document.getElementById(mappingLabels[playerObj.pos]);
  if (!tile) return;
  const placeholder = tile.querySelector(".player-placeholder");

  // Append if not already there
  if (!placeholder.contains(playerObj.element)) {
    placeholder.appendChild(playerObj.element);
  }
}

function getRandomPosition(diceEl, container) {
  const containerRect = container.getBoundingClientRect();
  const diceRect = diceEl.getBoundingClientRect();
  
  const maxX = containerRect.width - diceRect.width;
  const maxY = containerRect.height - diceRect.height;
  
  return {
    left: Math.random() * maxX,
    top: Math.random() * maxY
  };
}

const diceFaces = [
  "../../Assets/dice1.png",
  "../../Assets/dice2.png",
  "../../Assets/dice3.png",
  "../../Assets/dice4.png",
  "../../Assets/dice5.png",
  "../../Assets/dice6.png"
];

function animateDice(diceEl, face) {
    const container = diceEl.parentElement;
    if (!container) return; // safety check

    // start position: top center
    diceEl.style.top = "0px";
    diceEl.style.left = `${(container.clientWidth/2 - 25)}px`;
    diceEl.textContent = "🎲"; // initial throw emoji

    // random scatter target within container bounds
    const pos = getRandomPosition(diceEl, container);

    // rotate randomly while "falling"
    const rotations = Math.floor(Math.random() * 720) + 360;
    diceEl.style.transform = `rotate(${rotations}deg)`;

    // animate after a tiny delay to let DOM register
    setTimeout(() => {
        diceEl.style.top = `${pos.top}px`;
        diceEl.style.left = `${pos.left}px`;
    }, 50);

    // after animation, show final face
    setTimeout(() => {
      diceEl.style.backgroundSize = "contain";
      diceEl.style.backgroundRepeat = "no-repeat";
      diceEl.style.backgroundPosition = "center";
      diceEl.style.backgroundImage = `url(${face})`;
      diceEl.textContent = "";
    }, 800); // matches transition time

    diceEl.innerHTML = `<img src="${face}" width="50" height="50" />`;

}



// 7. Roll dice
async function rollDice() {
  try {
    const res = await fetch("../../Backend/mBoard/rollDice.php");
    const data = await res.json();
    const die1 = data.die1;
    const die2 = data.die2;
    const total = die1 + die2;

    animateDice(document.getElementById("dice1"), diceFaces[die1-1]);
    animateDice(document.getElementById("dice2"), diceFaces[die2-1]);
    diceResult.textContent = `Player ${players[currentPlayerIndex].id} rolled ${die1} + ${die2} = ${total}`;

    let p = players[currentPlayerIndex];
    p.pos = (p.pos + total) % 40;

    movePlayer(p);

    currentPlayerIndex = (currentPlayerIndex + 1) % players.length;
  } catch (err) {
    console.error("Dice error:", err);
  }
}

// 8. Init
initPlayers();
rollBtn.addEventListener("click", rollDice);
