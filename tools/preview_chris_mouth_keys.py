"""Render two diagnostic close-ups from the loaded Chris Blender scene."""

import os
import bpy
from mathutils import Vector

scene = bpy.context.scene
mesh = bpy.data.objects["Chris_Rigged_Body"]
keys = mesh.data.shape_keys.key_blocks
scene.timeline_markers.clear()
scene.frame_set(110)
camera = bpy.data.objects["Chris_Morning_Close_Camera"].copy()
camera.data = camera.data.copy()
scene.collection.objects.link(camera)
camera.name = "Chris_Speaking_Test_Close_Camera"
camera.location = (-0.1, -0.7, 1.05)
target = Vector((-0.1, 1.955, 0.9))
camera.rotation_euler = (target - camera.location).to_track_quat("-Z", "Y").to_euler()
camera.data.lens = 68
scene.camera = camera
scene.render.resolution_x = 640
scene.render.resolution_y = 360
scene.render.resolution_percentage = 100
scene.render.image_settings.file_format = "PNG"
output = os.path.join(os.path.dirname(bpy.data.filepath), "renders", "chris-speaking-test")
os.makedirs(output, exist_ok=True)

for name, mouth_open in (("closed", 0.0), ("open", 1.0)):
    keys["Mouth_AA"].value = mouth_open
    keys["Mouth_Open"].value = mouth_open
    scene.render.filepath = os.path.join(output, f"mouth-{name}.png")
    bpy.ops.render.render(write_still=True)
    print("Rendered", scene.render.filepath)
