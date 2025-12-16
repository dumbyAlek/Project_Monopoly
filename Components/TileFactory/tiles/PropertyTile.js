// PropertyTile.js
import { Tile } from "../Tile.js";

export class PropertyTile extends Tile {
  constructor(props) {
    super(props);
    this.category = "property";
  }
}
