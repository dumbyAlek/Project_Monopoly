// TileFactory.js
export class TileFactory {
  static registry = new Map();

  static register(type, tileClass) {
    TileFactory.registry.set(type, tileClass);
  }

  static create(type, props) {
    const TileClass = TileFactory.registry.get(type);
    if (!TileClass) {
      throw new Error(`Tile type "${type}" not registered`);
    }
    return new TileClass(props);
  }
}