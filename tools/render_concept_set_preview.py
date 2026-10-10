import bpy
import os

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")
OUT = os.path.join(ROOT, "renders", "chris-concept-set-preview.png")

bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene
scene.render.resolution_x = 960
scene.render.resolution_y = 540
scene.render.filepath = OUT

cam = bpy.data.objects.get("Chris_Speaking_Test_Close_Camera") or scene.camera
if cam:
    scene.camera = cam

bpy.ops.render.render(write_still=True)
print("Rendered concept set preview to", OUT)
