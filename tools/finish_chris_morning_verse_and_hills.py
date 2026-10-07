import bpy, math, os
from mathutils import Vector
ROOT = r'C:\\Users\\Greg\\Documents\\Beyond_OS\\dailybreath\\assets\\videos'
SRC = os.path.join(ROOT, 'DB_Christian_StudyV14_Morning_Verse_Timeline.blend')
OUT = os.path.join(ROOT, 'DB_Christian_StudyV15_Chris_Morning_Verse_Forest.blend')
HILLS_SRC = os.path.join(ROOT, 'THE HILLS V0.7.blend')
HILLS_OUT = os.path.join(ROOT, 'THE HILLS V0.8_Chris_Morning_Verse.blend')
PASSAGE = 'You will keep whoever’s mind is steadfast\\nin perfect peace, because he trusts in you.'
REFERENCE = 'ISAIAH 26:3'
NARRATION = "Welcome to Daily Breath’s Verse of the Day. Today’s reading is Isaiah chapter 26, verse 3. You will keep whoever’s mind is steadfast in perfect peace, because he trusts in you. May these words bring you peace and steady your heart today."


def font_material(name, color):
    mat=bpy.data.materials.get(name) or bpy.data.materials.new(name)
    mat.diffuse_color=(*color,1)
    return mat

def set_font(obj, body, size, color, align='CENTER'):
    obj.data.body=body
    obj.data.size=size
    obj.data.align_x=align
    obj.data.align_y='CENTER'
    obj.data.extrude=0.002
    obj.data.materials.clear(); obj.data.materials.append(font_material('DB_Mat_Verse_Cream', color))

def ensure_passage(prefix, x, y, z, rot):
    o=bpy.data.objects.get(prefix+'_Passage')
    if not o:
        c=bpy.data.curves.new(prefix+'_Passage', 'FONT')
        o=bpy.data.objects.new(prefix+'_Passage', c)
        bpy.context.scene.collection.objects.link(o)
    o.location=(x,y,z); o.rotation_euler=rot
    set_font(o,PASSAGE,0.18,(0.96,0.91,0.78))
    return o

def update_study():
    bpy.ops.wm.open_mainfile(filepath=SRC)
    scene=bpy.context.scene
    # Forest-green feature wall. Leave side walls light and warm.
    wall=bpy.data.objects.get('DB_Study_Back_Wall')
    if wall:
        green=font_material('DB_Mat_Forest_Green',(0.055,0.18,0.105))
        wall.data.materials.clear(); wall.data.materials.append(green)
    set_font(bpy.data.objects['DB_Study_Verse_Title'], 'TODAY’S VERSE', .22, (0.96,0.91,0.78))
    set_font(bpy.data.objects['DB_Study_Verse_Reference'], REFERENCE, .20, (0.91,0.75,0.38))
    bpy.data.objects['DB_Study_Verse_Title'].location.z=2.35
    bpy.data.objects['DB_Study_Verse_Reference'].location.z=1.65
    ensure_passage('DB_Study_Verse',2.25,2.68,1.98,(math.radians(90),0,0))
    # The user requested the desk turn. Apply an actual 180 degree rotation on the desk root.
    desk=bpy.data.objects.get('ChrisStudy_Desk_Root')
    if desk:
        desk.rotation_euler.z=math.radians(180)
        desk['orientation']='Audience-facing desk: rotated 180 degrees for the presenter shot.'
    scene['show_title']='Chris · Morning Verse'
    scene['spoken_script']=NARRATION
    scene['verse_reference']=REFERENCE
    scene['verse_text']=PASSAGE.replace('\\n',' ')
    scene['subtitle_note']='Reserved for subtitle pass; no bottom subtitles are baked into this template.'
    scene.frame_start=1; scene.frame_end=216; scene.render.fps=24
    scene.render.resolution_x=1280; scene.render.resolution_y=720; scene.render.resolution_percentage=100
    scene.render.image_settings.file_format='PNG'
    scene.render.filepath=os.path.join(ROOT,'renders','chris-morning-verse-test-')
    scene.render.engine='BLENDER_EEVEE'
    scene.render.image_settings.file_format='PNG'
    os.makedirs(os.path.dirname(scene.render.filepath),exist_ok=True)
    bpy.ops.wm.save_as_mainfile(filepath=OUT)
    print('SAVED_STUDY',OUT)

def update_hills():
    bpy.ops.wm.open_mainfile(filepath=HILLS_SRC)
    wall=bpy.data.objects.get('TH_ChrisStudy_Back_Wall')
    if wall:
        green=font_material('DB_Mat_Forest_Green',(0.055,0.18,0.105))
        wall.data.materials.clear(); wall.data.materials.append(green)
    title=bpy.data.objects.get('TH_ChrisStudy_Verse_Title')
    ref=bpy.data.objects.get('TH_ChrisStudy_Verse_Reference')
    if title:
        set_font(title,'TODAY’S VERSE',.22,(0.96,0.91,0.78)); title.location.z=2.70
    if ref:
        set_font(ref,REFERENCE,.20,(0.91,0.75,0.38)); ref.location.z=2.05
    ensure_passage('TH_ChrisStudy_Verse',-19.75,50.68,2.35,(math.radians(90),0,0))
    bpy.context.scene['chris_morning_verse_script']=NARRATION
    bpy.context.scene['subtitle_note']='Bottom subtitles will be added in the edit pass.'
    bpy.ops.wm.save_as_mainfile(filepath=HILLS_OUT)
    print('SAVED_HILLS',HILLS_OUT)

update_study(); update_hills()

