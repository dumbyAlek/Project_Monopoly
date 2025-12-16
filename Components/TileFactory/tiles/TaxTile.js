// TaxTile.js
import { Tile } from "../Tile.js";

export class TaxTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "tax";
  }
}
