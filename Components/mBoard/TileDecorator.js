// TileDecorator.js
export class TileDecorator {
  static addHouses(tileEl, count = 0) {
    const container = tileEl.querySelector(".houses-container");
    container.innerHTML = "";
    for (let i = 0; i < count; i++) {
      const house = document.createElement("img");
      house.src = "../../Assets/house.webp";
      container.appendChild(house);
    }
  }

  static addHotel(tileEl) {
    const container = tileEl.querySelector(".houses-container");
    container.innerHTML = "";
    const hotel = document.createElement("img");
    hotel.src = "../../Assets/hotel.webp";
    hotel.classList.add("hotel");
    container.appendChild(hotel);
  }

  static addSections(tileEl, sections = []) {
    let container = tileEl.querySelector(".tile-sections");
    if (!container) {
      container = document.createElement("div");
      container.classList.add("tile-sections");
      tileEl.appendChild(container);
    }
    container.innerHTML = "";
    sections.forEach(sec => {
      const secDiv = document.createElement("div");
      secDiv.classList.add("section");
      secDiv.textContent = sec;
      container.appendChild(secDiv);
    });
  }

}
