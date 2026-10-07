import bpy, bmesh, os
from collections import defaultdict
ROOT=r'C:\\Users\\Greg\\Documents\\Beyond_OS\\dailybreath\\assets\\videos'
SRC=os.path.join(ROOT,'DB_Christian_StudyV15_Chris_Morning_Verse_Forest.blend')
OUT=os.path.join(ROOT,'DB_Christian_StudyV16_Chris_Morning_Verse_Test.blend')
bpy.ops.wm.open_mainfile(filepath=SRC)
# Remove the detached chair geometry from the joined Tripo desk mesh. Its 324-vertex disconnected island was sitting beside Chris.
o=bpy.data.objects['ChrisStudy_Desk_Root']; me=o.data
adj=defaultdict(list)
for p in me.polygons:
 vs=list(p.vertices)
 for i,v in enumerate(vs): adj[v].extend(vs[:i]+vs[i+1:])
seen=set(); comps=[]
for v in range(len(me.vertices)):
 if v in seen: continue
 stack=[v]; seen.add(v); comp=[]
 while stack:
  a=stack.pop();comp.append(a)
  for n in adj[a]:
   if n not in seen: seen.add(n);stack.append(n)
 comps.append(comp)
# preserve largest component (desk); remove all disconnected components, including loose chair and tiny artifacts.
keep=set(max(comps,key=len))
bm=bmesh.new();bm.from_mesh(me)
bmesh.ops.delete(bm,geom=[v for v in bm.verts if v.index not in keep],context='VERTS')
bm.to_mesh(me);bm.free();me.update()
o['chair_removed']='Detached chair island removed: Chris is seated behind the desk without a stray side chair.'
# Forest green material needs Principled shader inputs in Blender 5.2, not only diffuse viewport colour.
mat=bpy.data.materials.get('DB_Mat_Forest_Green') or bpy.data.materials.new('DB_Mat_Forest_Green')
mat.use_nodes=True
bsdf=mat.node_tree.nodes.get('Principled BSDF')
if bsdf:
 bsdf.inputs['Base Color'].default_value=(0.028,0.115,0.060,1)
 bsdf.inputs['Roughness'].default_value=.72
wall=bpy.data.objects.get('DB_Study_Back_Wall')
wall.data.materials.clear(); wall.data.materials.append(mat)
# Test only the opening: clear, quick 4 seconds at 640p.
scene=bpy.context.scene
scene.frame_start=1; scene.frame_end=96; scene.render.fps=24
scene.render.resolution_x=640;scene.render.resolution_y=360;scene.render.resolution_percentage=100
scene.render.image_settings.file_format='PNG'
scene.render.filepath=os.path.join(ROOT,'renders','test_frames','frame_')
os.makedirs(os.path.dirname(scene.render.filepath),exist_ok=True)
scene['test_video']='4-second silent visual test. Narration WAV and subtitles are deliberately not baked yet.'
bpy.ops.wm.save_as_mainfile(filepath=OUT)
print('SAVED',OUT)
