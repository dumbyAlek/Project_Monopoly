// ChanceTile.js
import { Tile } from "../Tile.js";

export class ChanceTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "chance";
  }
}
