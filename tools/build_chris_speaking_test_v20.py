"""Create a non-destructive Chris speaking-test scene with an animated mouth overlay.

The original generated mouth shape keys deform too much of Chris's stylized
face. This test uses a compact, bone-parented mouth object whose scale changes
on a conversational cadence. The final scene remains editable for a phoneme
pass when the Prayan MP3 is available.
"""

import bpy
import math
import os

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV19_Realistic_Set_Assets.blend")
OUTPUT = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")

bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene
scene.timeline_markers.clear()

# Clear a prior test object only in this newly opened in-memory scene.
old = bpy.data.objects.get("Chris_Speaking_Mouth")
if old:
    bpy.data.objects.remove(old, do_unlink=True)

for key_name in ("Mouth_Open", "Mouth_MBP", "Mouth_FV", "Mouth_EE", "Mouth_OH", "Mouth_AA"):
    key = bpy.data.objects["Chris_Rigged_Body"].data.shape_keys.key_blocks.get(key_name)
    if key:
        key.value = 0.0

mat = bpy.data.materials.get("Chris_Speaking_Mouth_Interior") or bpy.data.materials.new("Chris_Speaking_Mouth_Interior")
mat.diffuse_color = (0.095, 0.018, 0.012, 1)
mat.use_nodes = True
mat.node_tree.nodes["Principled BSDF"].inputs["Base Color"].default_value = (0.095, 0.018, 0.012, 1)
mat.node_tree.nodes["Principled BSDF"].inputs["Roughness"].default_value = 0.68

# Chris faces negative Y. This shallow sphere sits just in front of the face,
# reads as a mouth opening, and is much safer than deforming the entire mesh.
bpy.ops.mesh.primitive_uv_sphere_add(segments=24, ring_count=12, location=(-0.10, 1.902, 0.855))
mouth = bpy.context.object
mouth.name = "Chris_Speaking_Mouth"
mouth.scale = (0.055, 0.006, 0.014)
bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
mouth.data.materials.append(mat)

# Keep this first test overlay in world space. The current seated performance
# has no head translation, while a bone-parent would require a hand-tuned
# inverse matrix and previously displaced the mouth off Chris's face.

# A restrained five-second conversational cadence; replace these with phonemes
# after importing the Prayan WAV/MP3. Frame 1 is closed, each cycle opens and
# returns to a closed lip seal.
scene.render.fps = 12
scene.frame_start = 1
scene.frame_end = 60
base_x, base_y, base_z = 1.0, 1.0, 1.0
mouth.scale = (base_x, base_y, base_z)
mouth.keyframe_insert(data_path="scale", frame=1)
for frame in range(7, scene.frame_end + 1, 8):
    phase = (frame // 8) % 6
    if phase in (0, 3):
        scale = (1.00, 1.0, 0.62)  # lip closure / consonant
    elif phase == 1:
        scale = (1.28, 1.0, 2.55)  # open vowel
    elif phase == 2:
        scale = (0.80, 1.0, 2.05)  # rounded vowel
    elif phase == 4:
        scale = (1.45, 1.0, 1.48)  # broad vowel
    else:
        scale = (1.05, 1.0, 1.18)  # relaxed transition
    mouth.scale = scale
    mouth.keyframe_insert(data_path="scale", frame=frame)

# Face-framed close camera, independent of the production timeline cameras.
source_camera = bpy.data.objects["Chris_Morning_Close_Camera"]
camera = source_camera.copy()
camera.data = source_camera.data.copy()
scene.collection.objects.link(camera)
camera.name = "Chris_Speaking_Test_Close_Camera"
camera.location = (-0.10, -0.68, 1.06)
target = bpy.data.objects["Chris_Rigged_Body"].matrix_world.translation + bpy.mathutils.Vector((0, -0.20, 0.96)) if False else None
# Camera is aimed with an explicit quaternion so it cannot be overridden by old markers.
from mathutils import Vector
target = Vector((-0.10, 1.98, 0.93))
camera.rotation_euler = (target - camera.location).to_track_quat("-Z", "Y").to_euler()
camera.data.lens = 66
scene.camera = camera

scene["narrator"] = "Chris · Prayan (ElevenLabs)"
scene["speaking_test_status"] = "Mouth overlay animation ready; replace cadence with Prayan phoneme timing after audio export."
scene["prayan_voice_id"] = "Z6T21S2OyYi1iLYXumk4"
scene.render.resolution_x = 480
scene.render.resolution_y = 270
scene.render.resolution_percentage = 100
scene.render.image_settings.file_format = "PNG"
scene.render.filepath = os.path.join(ROOT, "renders", "chris-speaking-test", "frames", "frame_")
os.makedirs(os.path.dirname(scene.render.filepath), exist_ok=True)
bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
bpy.ops.render.render(animation=True)
print("Saved", OUTPUT)
print("Rendered frame sequence", scene.render.filepath)
