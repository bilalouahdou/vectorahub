import os
import subprocess
import sys

try:
    import vtracer
except ImportError:
    print("You need to install vtracer: python -m pip install vtracer")
    sys.exit(1)

from PIL import Image

# === CONFIG ===
waifu2x_dir = r"C:\waifu2x-ncnn-vulkan-20230413-win64"  # <-- Update to your waifu2x folder!
waifu2x_exe = os.path.join(waifu2x_dir, "waifu2x-ncnn-vulkan.exe" if os.name == "nt" else "waifu2x-ncnn-vulkan")
scale = 4       # upscale 2x (use 4 for small art if needed)
noise = 3        # 0-3 (3 = max noise reduction)
model = "models-upconv_7_anime_style_art_rgb"  # Pick your model folder name

def run_waifu2x(input_path, output_path):
    cmd = [
        waifu2x_exe,
        "-i", input_path,
        "-o", output_path,
        "-n", str(noise),
        "-s", str(scale),
        "-m", model
    ]
    print(f"\n[waifu2x] Upscaling with command: {' '.join(cmd)}\n")
    result = subprocess.run(cmd, capture_output=True)
    if result.returncode != 0:
        print(result.stderr.decode())
        raise RuntimeError("waifu2x failed!")
    print(f"Upscaled image saved to: {output_path}")

def main():
    print("==== waifu2x + VTracer FULLY AUTOMATIC ====")
    inp = input("Enter path to your PNG/JPG: ").strip()
    if not os.path.isfile(inp):
        print("File not found.")
        return

    # Upscaled output path
    upscaled = os.path.splitext(inp)[0] + "_waifu2x.png"

    # Step 1: Upscale using waifu2x
    run_waifu2x(inp, upscaled)

    # Step 2: Vectorize with VTracer (cartoon/clean settings)
    out_svg = os.path.splitext(inp)[0] + "_svg.svg"
    print("\n[VTracer] Vectorizing with ultra-smooth settings...")
    vtracer.convert_image_to_svg_py(
        upscaled,
        out_svg,
        colormode="color",
        mode="spline",
        filter_speckle=12,
        color_precision=8,
        layer_difference=16,
        corner_threshold=55,
        length_threshold=3.0,
        max_iterations=15,
        splice_threshold=55,
        path_precision=3
    )
    print(f"\n✅ DONE! SVG saved at: {out_svg}")

    # Clean up: Delete the upscaled PNG after vectorization
    try:
        os.remove(upscaled)
        print(f"Deleted temporary upscaled image: {upscaled}")
    except Exception as e:
        print(f"Warning: Could not delete upscaled image ({upscaled}): {e}")

if __name__ == "__main__":
    main()
