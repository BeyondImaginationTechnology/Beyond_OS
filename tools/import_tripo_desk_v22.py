"""Import Tripo3D GLB desk & chair model into Chris's Blender V20 study set and scale for executive proportions.

Run with Blender 5.2 in background mode.
"""

import bpy
import math
import os
from mathutils import Vector

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
BLEND_PATH = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")
GLB_PATH = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\blender\props\office-desk-tripo-v1\office-desk-3d-model.glb"
PREVIEW_PATH = os.path.join(ROOT, "renders", "chris-tripo-desk-preview.png")

print("Opening", BLEND_PATH)
bpy.ops.wm.open_mainfile(filepath=BLEND_PATH)
scene = bpy.context.scene

# Hide old geometric box desk placeholders
for obj in list(bpy.data.objects):
    if obj.name.startswith(("Concept_Desk_", "DB_Table_")):
        obj.hide_render = True
        obj.hide_set(True)

# Clean previous Tripo collection
coll_name = "Tripo3D_Office_Desk_Set"
old_coll = bpy.data.collections.get(coll_name)
if old_coll:
    for obj in list(old_coll.objects):
        bpy.data.objects.remove(obj, do_unlink=True)
    bpy.data.collections.remove(old_coll)

tripo_coll = bpy.data.collections.new(coll_name)
scene.collection.children.link(tripo_coll)

before_objs = set(bpy.data.objects)

print("Importing GLTF model from", GLB_PATH)
bpy.ops.import_scene.gltf(filepath=GLB_PATH)

after_objs = set(bpy.data.objects)
imported_objs = list(after_objs - before_objs)

# Move imported objects to Tripo collection
for obj in imported_objs:
    for c in list(obj.users_collection):
        c.objects.unlink(obj)
    tripo_coll.objects.link(obj)

root_objs = [o for o in imported_objs if o.parent is None]

# Scale model by 2.25x so width is ~2.20m and height is ~0.82m
scale_factor = 2.25
for o in root_objs:
    o.scale *= scale_factor

bpy.ops.object.select_all(action='DESELECT')
for o in imported_objs:
    o.select_set(True)
bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)

# Calculate transformed bounds
min_xyz = Vector((float('inf'), float('inf'), float('inf')))
max_xyz = Vector((float('-inf'), float('-inf'), float('-inf')))

for obj in imported_objs:
    if obj.type == 'MESH':
        for corner in obj.bound_box:
            world_corner = obj.matrix_world @ Vector(corner)
            min_xyz.x = min(min_xyz.x, world_corner.x)
            min_xyz.y = min(min_xyz.y, world_corner.y)
            min_xyz.z = min(min_xyz.z, world_corner.z)
            max_xyz.x = max(max_xyz.x, world_corner.x)
            max_xyz.y = max(max_xyz.y, world_corner.y)
            max_xyz.z = max(max_xyz.z, world_corner.z)

size = max_xyz - min_xyz
center = (min_xyz + max_xyz) / 2.0

print(f"Scaled model bounds: min={min_xyz}, max={max_xyz}, size={size}, center={center}")

# Position Tripo3D executive desk to ground level z=0, y=1.38, x=-0.10
target_center_x = -0.10
target_center_y = 1.38
target_bottom_z = 0.0

offset_x = target_center_x - center.x
offset_y = target_center_y - center.y
offset_z = target_bottom_z - min_xyz.z

for o in root_objs:
    o.location += Vector((offset_x, offset_y, offset_z))

# Camera setup for presenter preview
cam = bpy.data.objects.get("Chris_Speaking_Test_Close_Camera") or scene.camera
if cam:
    scene.camera = cam

scene.render.resolution_x = 1280
scene.render.resolution_y = 720
scene.render.resolution_percentage = 100
scene.render.filepath = PREVIEW_PATH

bpy.ops.wm.save_as_mainfile(filepath=BLEND_PATH)
print("Saved updated blend file with scaled Tripo3D executive desk to", BLEND_PATH)

os.makedirs(os.path.dirname(PREVIEW_PATH), exist_ok=True)
bpy.ops.render.render(write_still=True)
print("Rendered preview to", PREVIEW_PATH)
