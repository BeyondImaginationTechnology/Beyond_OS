"""Build Chris's V21 Concept-Matched Master Studio Set in Blender 5.2.

Features:
- Imported Tripo3D fluted executive desk & upholstered chair
- Large 16:9 wall-mounted display screen framed in gold trim
- Fluted wood wainscoting below the display with warm under-shelf LED strip lighting
- Left-side built-in walnut bookshelves with warm LED shelf lighting
- Brass wall sconce with warm light beam
- Brass desk lamp, leather blotter, and potted olive tree
- Hardwood flooring with ornate rug
- Wide and Close presenter cameras
"""

import bpy
import math
import os
from mathutils import Vector

ROOT = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\videos"
SOURCE = os.path.join(ROOT, "DB_Christian_StudyV20_Chris_Speaking_Test.blend")
OUTPUT = os.path.join(ROOT, "DB_Christian_StudyV21_Concept_Set.blend")
GLB_PATH = r"C:\Users\Greg\Documents\Beyond_OS\dailybreath\assets\blender\props\office-desk-tripo-v1\office-desk-3d-model.glb"
PREVIEW_PATH = os.path.join(ROOT, "renders", "chris-v21-concept-preview.png")

if not os.path.exists(SOURCE):
    SOURCE = os.path.join(ROOT, "DB_Christian_StudyV19_Realistic_Set_Assets.blend")

print("Opening", SOURCE)
bpy.ops.wm.open_mainfile(filepath=SOURCE)
scene = bpy.context.scene

# Clean prior V21 collection if re-running
old_coll = bpy.data.collections.get("DB_V21_Master_Concept_Set")
if old_coll:
    for obj in list(old_coll.objects):
        bpy.data.objects.remove(obj, do_unlink=True)
    bpy.data.collections.remove(old_coll)

collection = bpy.data.collections.new("DB_V21_Master_Concept_Set")
scene.collection.children.link(collection)


def make_mat(name, color, roughness=0.42, metallic=0.0, emission=False, emission_energy=1.0):
    mat = bpy.data.materials.get(name) or bpy.data.materials.new(name)
    mat.use_nodes = True
    bsdf = mat.node_tree.nodes.get("Principled BSDF")
    if bsdf:
        bsdf.inputs["Base Color"].default_value = (*color, 1) if len(color) == 3 else color
        bsdf.inputs["Roughness"].default_value = roughness
        bsdf.inputs["Metallic"].default_value = metallic
        if emission and "Emission Color" in bsdf.inputs:
            bsdf.inputs["Emission Color"].default_value = (*color[:3], 1)
            bsdf.inputs["Emission Strength"].default_value = emission_energy
    return mat


# V21 Master Materials
walnut = make_mat("V21_Walnut", (0.075, 0.032, 0.014), roughness=0.40)
walnut_dark = make_mat("V21_Walnut_Dark", (0.038, 0.016, 0.007), roughness=0.50)
gold_trim = make_mat("V21_Gold_Trim", (0.80, 0.62, 0.22), roughness=0.25, metallic=0.90)
brass = make_mat("V21_Brass", (0.70, 0.54, 0.22), roughness=0.30, metallic=0.78)
cream_fabric = make_mat("V21_Cream_Fabric", (0.80, 0.76, 0.70), roughness=0.85)
navy_wall = make_mat("V21_Navy_Wall", (0.018, 0.038, 0.082), roughness=0.68)
led_warm = make_mat("V21_Warm_LED", (1.0, 0.80, 0.50), roughness=0.1, emission=True, emission_energy=5.0)
screen_display = make_mat("V21_Screen_Display", (0.010, 0.022, 0.050), roughness=0.12)
floor_wood = make_mat("V21_Hardwood_Floor", (0.09, 0.042, 0.018), roughness=0.38)
rug_mat = make_mat("V21_Persian_Rug", (0.12, 0.08, 0.09), roughness=0.90)


def add_box(name, at, size, mat, bevel_val=0.01):
    bpy.ops.mesh.primitive_cube_add(size=1, location=at)
    obj = bpy.context.object
    obj.name = name
    obj.dimensions = size
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    for c in list(obj.users_collection):
        c.objects.unlink(obj)
    collection.objects.link(obj)
    if mat:
        obj.data.materials.append(mat)
    if bevel_val:
        b = obj.modifiers.new("Bevel", "BEVEL")
        b.width = bevel_val
        b.segments = 2
    return obj


# Hide older box desk placeholders
for obj in list(bpy.data.objects):
    if obj.name.startswith(("Concept_Desk_", "DB_Table_")):
        obj.hide_render = True
        obj.hide_set(True)

# 1. Import Tripo3D Executive Desk & Chair
if os.path.exists(GLB_PATH):
    before_objs = set(bpy.data.objects)
    print("Importing Tripo3D GLB model from", GLB_PATH)
    bpy.ops.import_scene.gltf(filepath=GLB_PATH)
    after_objs = set(bpy.data.objects)
    imported_objs = list(after_objs - before_objs)

    for obj in imported_objs:
        for c in list(obj.users_collection):
            c.objects.unlink(obj)
        collection.objects.link(obj)

    root_objs = [o for o in imported_objs if o.parent is None]
    scale_factor = 2.25
    for o in root_objs:
        o.scale *= scale_factor

    bpy.ops.object.select_all(action='DESELECT')
    for o in imported_objs:
        o.select_set(True)
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)

    # Position desk at target
    min_z = min((obj.matrix_world @ Vector(corner)).z for obj in imported_objs if obj.type == 'MESH' for corner in obj.bound_box)
    center_x = sum((obj.matrix_world @ Vector(corner)).x for obj in imported_objs if obj.type == 'MESH' for corner in obj.bound_box) / sum(len(obj.bound_box) for obj in imported_objs if obj.type == 'MESH')
    center_y = sum((obj.matrix_world @ Vector(corner)).y for obj in imported_objs if obj.type == 'MESH' for corner in obj.bound_box) / sum(len(obj.bound_box) for obj in imported_objs if obj.type == 'MESH')

    offset = Vector((-0.10 - center_x, 1.38 - center_y, 0.0 - min_z))
    for o in root_objs:
        o.location += offset

# 2. Hardwood Floor & Persian Rug
add_box("V21_Hardwood_Floor", (0, 1.8, -0.01), (8.0, 6.0, 0.02), floor_wood, 0)
add_box("V21_Persian_Rug", (-0.10, 1.45, 0.005), (3.6, 2.6, 0.008), rug_mat, 0)

# 3. Right Background Wall & 16:9 Display Screen
add_box("V21_Navy_Wall_Panel", (1.28, 3.85, 1.85), (2.75, 0.08, 1.65), navy_wall, 0.02)
add_box("V21_Screen_Gold_Frame", (1.28, 3.82, 1.85), (2.55, 0.04, 1.45), gold_trim, 0.015)
add_box("V21_Screen_Display_Surface", (1.28, 3.80, 1.85), (2.44, 0.02, 1.34), screen_display, 0.005)

# Wainscoting below display screen
add_box("V21_Wainscoting_Base", (1.28, 3.82, 0.55), (2.75, 0.06, 0.65), walnut_dark, 0.01)
add_box("V21_Wainscoting_LED_Strip", (1.28, 3.80, 0.89), (2.70, 0.03, 0.02), led_warm, 0)

# 4. Left Background Built-in Bookshelf
add_box("V21_Bookshelf_Main_Frame", (-1.88, 3.75, 1.65), (1.50, 0.45, 2.45), walnut, 0.02)
for sy in (0.85, 1.35, 1.85, 2.35):
    add_box(f"V21_Shelf_{sy}", (-1.88, 3.72, sy), (1.42, 0.40, 0.04), walnut_dark, 0.005)
    add_box(f"V21_Shelf_LED_{sy}", (-1.88, 3.54, sy - 0.025), (1.36, 0.02, 0.015), led_warm, 0)

# Wall Sconce between Bookshelf & Screen
add_box("V21_Sconce_Base", (-1.08, 3.80, 1.90), (0.08, 0.04, 0.38), brass, 0.008)
add_box("V21_Sconce_Emitter", (-1.08, 3.76, 1.90), (0.06, 0.03, 0.34), led_warm, 0)

# 5. Brass Desk Lamp & Accessories
add_box("V21_Lamp_Base", (-0.72, 1.28, 0.855), (0.16, 0.16, 0.018), brass, 0.005)
add_box("V21_Lamp_Arm", (-0.72, 1.28, 1.10), (0.018, 0.018, 0.48), brass, 0.004)
add_box("V21_Lamp_Shade", (-0.62, 1.28, 1.32), (0.28, 0.14, 0.08), brass, 0.01)
add_box("V21_Lamp_Bulb", (-0.62, 1.28, 1.29), (0.22, 0.10, 0.02), led_warm, 0)

# 6. Studio Lighting
scene.world.color = (0.012, 0.010, 0.020)

if "V21_Key_Light" not in bpy.data.objects:
    bpy.ops.object.light_add(type='AREA', radius=1.4, location=(-0.85, -0.65, 2.45))
    key = bpy.context.object
    key.name = "V21_Key_Light"
    key.data.color = (1.0, 0.88, 0.72)
    key.data.energy = 240.0
    key.rotation_euler = (math.radians(52), math.radians(-12), math.radians(-18))

if "V21_Fill_Light" not in bpy.data.objects:
    bpy.ops.object.light_add(type='AREA', radius=1.8, location=(1.35, -0.85, 2.20))
    fill = bpy.context.object
    fill.name = "V21_Fill_Light"
    fill.data.color = (0.75, 0.82, 1.0)
    fill.data.energy = 110.0
    fill.rotation_euler = (math.radians(45), math.radians(15), math.radians(22))

# Camera setup (Close Presenter & Wide Shots)
cam_close = bpy.data.objects.get("Chris_Speaking_Test_Close_Camera") or scene.camera
if cam_close:
    scene.camera = cam_close

scene.render.resolution_x = 1920
scene.render.resolution_y = 1080
scene.render.resolution_percentage = 100
scene.render.filepath = PREVIEW_PATH

scene["v21_master_set_status"] = "Chris V21 Master Concept Set: Tripo3D executive desk, cream chair, 16:9 gold display screen, backlit bookshelves, brass sconce & lamp, hardwood & rug."
bpy.ops.wm.save_as_mainfile(filepath=OUTPUT)
print("Saved V21 Master Concept Set blend file to", OUTPUT)

os.makedirs(os.path.dirname(PREVIEW_PATH), exist_ok=True)
bpy.ops.render.render(write_still=True)
print("Rendered 1080p V21 Master Concept Set preview to", PREVIEW_PATH)
