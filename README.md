# Simple 3D Showcase (WordPress Plugin)

Simple 3D Showcase is a lightweight WordPress plugin for embedding interactive 3D models with the [`<model-viewer>`](https://modelviewer.dev/) web component.

It is designed for quick product demos, exhibitions, kiosks, and portfolio pages where you want visitors to rotate and inspect a 3D model directly in the browser.

---

## Features

- Embed 3D models with a simple shortcode.
- Supports `.glb` and `.gltf` uploads in WordPress Media Library.
- Interactive controls out of the box:
  - Reset camera
  - Toggle auto-rotation
  - Fullscreen mode
- Configurable shortcode options (height, background, camera controls, etc.).

---

## Requirements

- WordPress (self-hosted)
- Modern browser with WebGL support
- Internet access to load `@google/model-viewer` from unpkg CDN

---

## Installation

### Option 1: Upload ZIP in WordPress Admin

1. Go to **Plugins → Add New → Upload Plugin**.
2. Upload `simple-3d-showcase.zip`.
3. Click **Install Now** and then **Activate**.

### Option 2: Manual install

1. Extract the ZIP.
2. Place `simple-3d-showcase.php` inside a folder named `simple-3d-showcase`.
3. Upload that folder to:
   ```
   wp-content/plugins/simple-3d-showcase/
   ```
4. Activate the plugin in **Plugins**.

---

## Usage

Insert the shortcode in a page/post:

```shortcode
[simple_3d_showcase model_url="https://example.com/wp-content/uploads/model.glb"]
```

### Shortcode attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `model_url` | string | `""` | URL to `.glb` or `.gltf` model (required). |
| `height` | string | `"600px"` | Height of the viewer container (e.g. `500px`, `70vh`). |
| `auto_rotate` | boolean string | `"true"` | Enables automatic model rotation on load. |
| `camera_controls` | boolean string | `"true"` | Enables mouse/touch camera controls. |
| `background` | color string | `"#dbdbdb"` | Viewer background color. |

### Example with options

```shortcode
[simple_3d_showcase 
  model_url="https://example.com/wp-content/uploads/chair.glb"
  height="500px"
  auto_rotate="false"
  camera_controls="true"
  background="#f5f5f5"
]
```

---

## Notes

- If `model_url` is missing, the plugin shows a message: **“No model_url provided.”**
- The viewer script is loaded in `wp_head` on pages where WordPress outputs the header.
- Large model files may impact load times. Optimize models before upload for better performance.

---

## License

GPL2+
