// TileFactory.js
export class TileFactory {
  static createTile(type, props = {}) {
    switch(type) {

      case "property":
        return { ...props, category: "property" };

      case "station":
        return { ...props, category: "station" };

      case "chance":
        return { ...props, category: "chance" };

      case "community":
        return { ...props, category: "community" };

      case "jail":
        return {
          ...props,
          category: "jail",
          isJail: true,
          sections: ["Just Visiting", "In Jail"]  // for display splitting
        };

      case "goToJail":
        return {
          ...props,
          category: "goToJail",
          isGoToJail: true  // used by game logic
        };

      case "go":
        return { ...props, category: "go" };

      case "tax":
        return { ...props, category: "tax" };

      case "utility":
        return { ...props, category: "utility" };

      case "free":
        return { ...props, category: "free" };

      default:
        return { ...props, category: "other" };
    }
  }
}