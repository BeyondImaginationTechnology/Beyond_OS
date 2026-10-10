"""Render a 5-second 1080p preview video of Chris speaking in his concept set in Blender 5.2.
"""

import bpy
import os

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")
OUT = os.path.join(ROOT, "renders", "chris-concept-set-preview.mp4")

os.makedirs(os.path.dirname(OUT), exist_ok=True)

bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene

# Video Render Settings (1080p)
scene.render.engine = 'BLENDER_EEVEE'
scene.render.resolution_x = 1920
scene.render.resolution_y = 1080
scene.render.resolution_percentage = 100
scene.render.fps = 24

# Set output format
try:
    scene.render.image_settings.file_format = 'FFMPEG'
    scene.render.ffmpeg.format = 'MPEG4'
    scene.render.ffmpeg.codec = 'H264'
    scene.render.ffmpeg.constant_rate_factor = 'MEDIUM'
except Exception:
    scene.render.image_settings.file_format = 'PNG'

scene.render.filepath = OUT

# 5-second preview range (120 frames at 24fps)
scene.frame_start = 1
scene.frame_end = 120

cam = bpy.data.objects.get("Chris_Speaking_Test_Close_Camera") or scene.camera
if cam:
    scene.camera = cam

print(f"Rendering 5-second preview video (120 frames) to {OUT}...")
bpy.ops.render.render(animation=True)
print(f"✅ Successfully rendered preview video: {OUT}")
