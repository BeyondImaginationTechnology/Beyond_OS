import bpy
for name in ['ChrisStudy_Desk_Root','Chris_Rig','Chris_Presenter_Camera','DB_Study_Back_Wall','DB_Study_Verse_Title','DB_Study_Verse_Reference']:
 o=bpy.data.objects.get(name)
 print(name, 'FOUND' if o else 'MISSING')
 if o:
  print(' loc',tuple(round(x,3) for x in o.location),'rot',tuple(round(x,3) for x in o.rotation_euler),'dim',tuple(round(x,3) for x in o.dimensions),'type',o.type, 'mats', [m.name for m in (o.data.materials if hasattr(o.data,'materials') else [])])
print('ALL objects containing Wall/Desk/Chair')
for o in bpy.data.objects:
 if any(s in o.name.lower() for s in ['wall','desk','chair']): print(o.name, o.type, tuple(round(x,2) for x in o.location),tuple(round(x,2) for x in o.dimensions))
